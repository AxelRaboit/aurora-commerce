<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\Conversation\Dto\MessageInputInterface;
use Aurora\Module\Assistant\Conversation\Entity\Conversation;
use Aurora\Module\Assistant\Conversation\Entity\ConversationInterface;
use Aurora\Module\Assistant\Conversation\Entity\Message;
use Aurora\Module\Assistant\Conversation\Entity\MessageInterface;
use Aurora\Module\Assistant\Conversation\Enum\MessageRoleEnum;
use Aurora\Module\Assistant\Conversation\Repository\ConversationRepository;
use Aurora\Module\Assistant\Llm\Contract\ChatClientInterface;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPointInterface;
use Aurora\Module\Assistant\MountPoint\Enum\MountPointAccessEnum;
use Aurora\Module\Assistant\MountPoint\Repository\AssistantMountPointRepository;
use Aurora\Module\Assistant\Setting\AssistantSettings;
use Aurora\Module\Assistant\Tool\Registry\ToolRegistry;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Throwable;

use function is_array;
use function is_string;
use function sprintf;

#[AsAlias(ConversationManagerInterface::class)]
class ConversationManager implements ConversationManagerInterface
{
    /** Safety cap on tool roundtrips in a single user turn — prevents infinite loops. */
    private const int MAX_TOOL_ROUNDTRIPS = 4;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ConversationRepository $conversationRepository,
        protected readonly ChatClientInterface $chatClient,
        protected readonly ToolRegistry $toolRegistry,
        protected readonly AuditLogger $auditLogger,
        protected readonly AssistantSettings $settings,
        protected readonly AssistantMountPointRepository $mountPointRepository,
    ) {}

    public function create(CoreUserInterface $user): ConversationInterface
    {
        $conversation = $this->createConversation();
        $conversation->setUser($user);
        $conversation->setAgency($user->getAgency());
        $conversation->setModel($this->chatClient->getModel());

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $this->auditCreated($conversation);

        return $conversation;
    }

    public function delete(ConversationInterface $conversation): void
    {
        $this->auditDeleted($conversation);

        $this->entityManager->remove($conversation);
        $this->entityManager->flush();
    }

    public function sendMessage(ConversationInterface $conversation, MessageInputInterface $input): ConversationInterface
    {
        $content = mb_trim($input->getContent());
        if ('' === $content) {
            throw new RuntimeException('Empty message');
        }

        $position = $this->nextPosition($conversation);

        $userMessage = $this->createMessage();
        $userMessage->setConversation($conversation);
        $userMessage->setRole(MessageRoleEnum::User);
        $userMessage->setContent($content);
        $userMessage->setPosition($position);

        $conversation->addMessage($userMessage);
        $this->entityManager->persist($userMessage);

        // First chat turn after the user message gets persisted so the
        // conversation reflects truth even if the LLM call fails mid-loop.
        $this->entityManager->flush();

        if (null === $conversation->getTitle()) {
            $conversation->setTitle($this->deriveTitle($content));
        }

        $this->runChatLoop($conversation);

        $this->entityManager->flush();

        return $conversation;
    }

    /**
     * Drives the multi-turn chat: send → optionally execute tool calls →
     * resend until the model emits an assistant message with no tool calls
     * (or we hit MAX_TOOL_ROUNDTRIPS).
     */
    protected function runChatLoop(ConversationInterface $conversation): void
    {
        $tools = $this->toolRegistry->describe();

        for ($i = 0; $i < self::MAX_TOOL_ROUNDTRIPS; ++$i) {
            $payload = $this->buildOllamaMessages($conversation);

            try {
                $response = $this->chatClient->chat($payload, $tools);
            } catch (Throwable $throwable) {
                $this->appendAssistantError($conversation, $throwable->getMessage());

                return;
            }

            $toolCalls = $response['tool_calls'];

            $assistant = $this->createMessage();
            $assistant->setConversation($conversation);
            $assistant->setRole(MessageRoleEnum::Assistant);
            $assistant->setContent($response['content']);
            $assistant->setToolCalls($toolCalls);
            $assistant->setPosition($this->nextPosition($conversation));
            $conversation->addMessage($assistant);
            $this->entityManager->persist($assistant);
            $this->entityManager->flush();

            if (null === $toolCalls || [] === $toolCalls) {
                return;
            }

            if ($this->anyToolRequiresConfirmation($toolCalls)) {
                $assistant->setAwaitingConfirmation(true);
                $this->entityManager->flush();

                return;
            }

            foreach ($toolCalls as $idx => $call) {
                $this->executeToolCall($conversation, $call, (string) $idx);
            }

            $this->entityManager->flush();
        }

        $this->appendAssistantError($conversation, sprintf('Tool roundtrip limit reached (%d).', self::MAX_TOOL_ROUNDTRIPS));
    }

    public function resumeAfterConfirmation(ConversationInterface $conversation, array $decisions): ConversationInterface
    {
        $pending = $this->findPendingAssistantMessage($conversation);
        if (!$pending instanceof MessageInterface) {
            return $conversation;
        }

        $toolCalls = $pending->getToolCalls() ?? [];
        foreach ($toolCalls as $idx => $call) {
            $id = isset($call['id']) && is_string($call['id']) && '' !== $call['id'] ? $call['id'] : (string) $idx;
            $decision = $decisions[$id] ?? 'reject';

            if ('approve' === $decision) {
                $this->executeToolCall($conversation, $call, $id);
            } else {
                $this->appendRejectedToolMessage($conversation, $call, $id);
            }
        }

        $pending->setAwaitingConfirmation(false);
        $this->entityManager->flush();

        $this->runChatLoop($conversation);
        $this->entityManager->flush();

        return $conversation;
    }

    /**
     * @param list<array<string, mixed>> $toolCalls
     */
    protected function anyToolRequiresConfirmation(array $toolCalls): bool
    {
        foreach ($toolCalls as $call) {
            $function = $call['function'] ?? [];
            $name = is_array($function) && isset($function['name']) && is_string($function['name']) ? $function['name'] : '';
            if ('' !== $name && $this->toolRegistry->requiresConfirmation($name)) {
                return true;
            }
        }

        return false;
    }

    protected function findPendingAssistantMessage(ConversationInterface $conversation): ?MessageInterface
    {
        foreach ($conversation->getMessages() as $message) {
            if (MessageRoleEnum::Assistant === $message->getRole() && $message->isAwaitingConfirmation()) {
                return $message;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $call
     */
    protected function appendRejectedToolMessage(ConversationInterface $conversation, array $call, string $toolCallId): void
    {
        $function = $call['function'] ?? [];
        $name = is_array($function) && isset($function['name']) && is_string($function['name']) ? $function['name'] : '';

        $message = $this->createMessage();
        $message->setConversation($conversation);
        $message->setRole(MessageRoleEnum::Tool);
        $message->setContent('The user rejected this action. Do not retry; explain or propose an alternative.');
        $message->setToolCallId($toolCallId);
        $message->setToolName($name);
        $message->setPosition($this->nextPosition($conversation));

        $conversation->addMessage($message);
        $this->entityManager->persist($message);
    }

    /**
     * @param array<string, mixed> $call
     */
    protected function executeToolCall(ConversationInterface $conversation, array $call, string $fallbackId): void
    {
        $function = $call['function'] ?? [];
        $name = is_array($function) && isset($function['name']) && is_string($function['name']) ? $function['name'] : '';
        $arguments = is_array($function) && isset($function['arguments']) ? $function['arguments'] : [];
        if (is_string($arguments)) {
            try {
                $decoded = json_decode($arguments, true, 512, JSON_THROW_ON_ERROR);
                $arguments = is_array($decoded) ? $decoded : [];
            } catch (JsonException) {
                $arguments = [];
            }
        }

        if (!is_array($arguments)) {
            $arguments = [];
        }

        $toolCallId = isset($call['id']) && is_string($call['id']) && '' !== $call['id'] ? $call['id'] : $fallbackId;

        if ('' === $name || !$this->toolRegistry->has($name)) {
            $result = sprintf('Error: unknown tool "%s".', $name);
        } else {
            try {
                $result = $this->toolRegistry->execute($name, $arguments, $conversation->getUser());
            } catch (Throwable $throwable) {
                $result = sprintf('Tool "%s" failed: %s', $name, $throwable->getMessage());
            }
        }

        $message = $this->createMessage();
        $message->setConversation($conversation);
        $message->setRole(MessageRoleEnum::Tool);
        $message->setContent($result);
        $message->setToolCallId($toolCallId);
        $message->setToolName($name);
        $message->setPosition($this->nextPosition($conversation));

        $conversation->addMessage($message);
        $this->entityManager->persist($message);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildOllamaMessages(ConversationInterface $conversation): array
    {
        $out = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($conversation)],
        ];

        foreach ($conversation->getMessages() as $message) {
            $out[] = $this->serializeMessageForOllama($message);
        }

        return $out;
    }

    /**
     * Builds the system message: the admin-configured prompt + a freshly-
     * rendered context block listing the user's active filesystem mount
     * points. Without this, the LLM has to guess at paths (or hallucinate
     * `/mnt/<thing>` conventions) before any tool call can succeed.
     */
    protected function buildSystemPrompt(ConversationInterface $conversation): string
    {
        $prompt = $this->settings->getSystemPrompt();
        $context = $this->renderMountPointContext($conversation);

        return '' === $context ? $prompt : $prompt."\n\n".$context;
    }

    protected function renderMountPointContext(ConversationInterface $conversation): string
    {
        $mountPoints = $this->mountPointRepository->findActiveForUser($conversation->getUser());
        if ([] === $mountPoints) {
            return 'Filesystem mount points: none configured. Tell the user to add one in /backend/assistant/mount-points before asking you to read files.';
        }

        $lines = ['Filesystem mount points available to filesystem_read / filesystem_write tools (use these exact paths — never invent paths like /mnt/...):'];
        foreach ($mountPoints as $mountPoint) {
            $lines[] = sprintf(
                '- "%s" → %s (%s)',
                $mountPoint->getName(),
                $mountPoint->getPath(),
                $this->describeAccess($mountPoint),
            );
        }

        return implode("\n", $lines);
    }

    protected function describeAccess(AssistantMountPointInterface $mountPoint): string
    {
        return match ($mountPoint->getAccess()) {
            MountPointAccessEnum::ReadWrite => 'read + write',
            default => 'read only',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeMessageForOllama(MessageInterface $message): array
    {
        $base = [
            'role' => $message->getRole()->value,
            'content' => $message->getContent(),
        ];

        if (MessageRoleEnum::Assistant === $message->getRole() && null !== $message->getToolCalls()) {
            $base['tool_calls'] = $message->getToolCalls();
        }

        if (MessageRoleEnum::Tool === $message->getRole()) {
            if (null !== $message->getToolCallId()) {
                $base['tool_call_id'] = $message->getToolCallId();
            }

            if (null !== $message->getToolName()) {
                $base['name'] = $message->getToolName();
            }
        }

        return $base;
    }

    protected function appendAssistantError(ConversationInterface $conversation, string $error): void
    {
        $message = $this->createMessage();
        $message->setConversation($conversation);
        $message->setRole(MessageRoleEnum::Assistant);
        $message->setContent('⚠ Assistant error: '.$error);
        $message->setPosition($this->nextPosition($conversation));

        $conversation->addMessage($message);
        $this->entityManager->persist($message);
    }

    protected function nextPosition(ConversationInterface $conversation): int
    {
        $max = $this->conversationRepository->findMaxPositionFor($conversation);

        return null === $max ? 0 : $max + 1;
    }

    protected function deriveTitle(string $firstUserContent): string
    {
        $oneLine = preg_replace('/\s+/', ' ', $firstUserContent) ?? $firstUserContent;
        $oneLine = mb_trim($oneLine);

        return mb_strlen($oneLine) > 60 ? mb_substr($oneLine, 0, 57).'…' : $oneLine;
    }

    /** Hook: client substitutes its concrete class here. */
    protected function createConversation(): ConversationInterface
    {
        return new Conversation();
    }

    /** Hook: client substitutes its concrete class here. */
    protected function createMessage(): MessageInterface
    {
        return new Message();
    }

    protected function auditCreated(ConversationInterface $conversation): void
    {
        $this->auditLogger->log('assistant', 'conversation.created', 'Conversation', $conversation->getId(), $this->auditPayload($conversation));
    }

    protected function auditDeleted(ConversationInterface $conversation): void
    {
        $this->auditLogger->log('assistant', 'conversation.deleted', 'Conversation', $conversation->getId(), $this->auditPayload($conversation));
    }

    /**
     * @return array<string, mixed>
     */
    protected function auditPayload(ConversationInterface $conversation): array
    {
        return [
            'model' => $conversation->getModel(),
            'user_id' => $conversation->getUser()->getId(),
        ];
    }
}
