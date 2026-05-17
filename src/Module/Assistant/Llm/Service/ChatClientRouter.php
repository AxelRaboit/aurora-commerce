<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Llm\Service;

use Aurora\Module\Assistant\Llm\Contract\ChatClientInterface;
use Aurora\Module\Assistant\Setting\AssistantSettings;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Delegates every {@see ChatClientInterface} call to the concrete client
 * selected by the admin in /backend/settings → Assistant → Provider.
 *
 * Adding a third provider = add a new client + a new case here.
 * The ConversationManager never needs to know which vendor is active.
 */
#[AsAlias(ChatClientInterface::class)]
final readonly class ChatClientRouter implements ChatClientInterface
{
    public function __construct(
        private AssistantSettings $settings,
        private OllamaChatClient $ollamaClient,
        private AnthropicChatClient $anthropicClient,
    ) {}

    public function getModel(): string
    {
        return $this->activeClient()->getModel();
    }

    public function chat(array $messages, array $tools = []): array
    {
        return $this->activeClient()->chat($messages, $tools);
    }

    private function activeClient(): ChatClientInterface
    {
        return match ($this->settings->getProvider()) {
            'anthropic' => $this->anthropicClient,
            default => $this->ollamaClient,
        };
    }
}
