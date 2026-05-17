<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Assistant\Conversation\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Assistant\Conversation\Dto\MessageInput;
use Aurora\Module\Assistant\Conversation\Entity\Conversation;
use Aurora\Module\Assistant\Conversation\Enum\MessageRoleEnum;
use Aurora\Module\Assistant\Conversation\Manager\ConversationManager;
use Aurora\Module\Assistant\Conversation\Repository\ConversationRepository;
use Aurora\Module\Assistant\Llm\Contract\ChatClientInterface;
use Aurora\Module\Assistant\MountPoint\Repository\AssistantMountPointRepository;
use Aurora\Module\Assistant\Setting\AssistantSettings;
use Aurora\Module\Assistant\Tool\Contract\ToolInterface;
use Aurora\Module\Assistant\Tool\Registry\ToolRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class ConversationManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ConversationRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ConversationRepository::class);
    }

    public function testCreateAssignsUserAndModel(): void
    {
        $manager = $this->makeManager(
            chatClient: $this->stubChatClient(model: 'qwen3:8b'),
        );

        $conversation = $manager->create($this->makeUser());

        self::assertSame('qwen3:8b', $conversation->getModel());
    }

    public function testSendMessagePersistsUserAndAssistantTurn(): void
    {
        $this->repository->method('findMaxPositionFor')->willReturn(null);

        $manager = $this->makeManager(
            chatClient: $this->stubChatClient(reply: 'Hello back.'),
        );

        $conversation = new Conversation();
        $conversation->setUser($this->makeUser());

        $manager->sendMessage($conversation, new MessageInput(content: 'Hi'));

        $messages = $conversation->getMessages();
        self::assertCount(2, $messages);
        self::assertSame(MessageRoleEnum::User, $messages[0]->getRole());
        self::assertSame('Hi', $messages[0]->getContent());
        self::assertSame(MessageRoleEnum::Assistant, $messages[1]->getRole());
        self::assertSame('Hello back.', $messages[1]->getContent());
        self::assertSame('Hi', $conversation->getTitle(), 'first user message seeds the title');
    }

    public function testToolCallRoundtripExecutesToolThenFinishes(): void
    {
        $this->repository->method('findMaxPositionFor')->willReturn(null);

        $tool = new class implements ToolInterface {
            public int $callCount = 0;

            public function getName(): string
            {
                return 'echo';
            }

            public function requiresConfirmation(): bool
            {
                return false;
            }

            public function getDescription(): string
            {
                return 'echo';
            }

            public function getParameterSchema(): array
            {
                return ['type' => 'object', 'properties' => []];
            }

            public function execute(array $arguments, CoreUserInterface $user): string
            {
                ++$this->callCount;

                return 'tool said: '.($arguments['msg'] ?? '');
            }
        };

        // Turn 1: model asks to call the tool. Turn 2: model finishes.
        $chatClient = new class($tool) implements ChatClientInterface {
            private int $turn = 0;

            public function __construct(private readonly ToolInterface $tool) {}

            public function getModel(): string
            {
                return 'test-model';
            }

            public function chat(array $messages, array $tools = []): array
            {
                ++$this->turn;
                if (1 === $this->turn) {
                    return [
                        'role' => 'assistant',
                        'content' => '',
                        'tool_calls' => [[
                            'id' => 'call_1',
                            'function' => ['name' => $this->tool->getName(), 'arguments' => ['msg' => 'ping']],
                        ]],
                    ];
                }

                return ['role' => 'assistant', 'content' => 'done', 'tool_calls' => null];
            }
        };

        $manager = $this->makeManager(chatClient: $chatClient, tools: [$tool]);

        $conversation = new Conversation();
        $conversation->setUser($this->makeUser());

        $manager->sendMessage($conversation, new MessageInput(content: 'go'));

        $roles = array_map(static fn ($m) => $m->getRole()->value, $conversation->getMessages()->toArray());
        self::assertSame(['user', 'assistant', 'tool', 'assistant'], $roles);
        self::assertSame(1, $tool->callCount);

        $toolMessage = $conversation->getMessages()->toArray()[2];
        self::assertStringContainsString('tool said: ping', $toolMessage->getContent());
        self::assertSame('call_1', $toolMessage->getToolCallId());
        self::assertSame('echo', $toolMessage->getToolName());
    }

    public function testChatClientFailureSurfacesAsAssistantError(): void
    {
        $this->repository->method('findMaxPositionFor')->willReturn(null);

        $chatClient = new class implements ChatClientInterface {
            public function getModel(): string
            {
                return 'broken';
            }

            public function chat(array $messages, array $tools = []): array
            {
                throw new RuntimeException('boom');
            }
        };

        $manager = $this->makeManager(chatClient: $chatClient);

        $conversation = new Conversation();
        $conversation->setUser($this->makeUser());

        $manager->sendMessage($conversation, new MessageInput(content: 'hi'));

        $messages = $conversation->getMessages()->toArray();
        self::assertCount(2, $messages);
        self::assertSame(MessageRoleEnum::Assistant, $messages[1]->getRole());
        self::assertStringContainsString('boom', $messages[1]->getContent());
        self::assertStringContainsString('Assistant error', $messages[1]->getContent());
    }

    public function testWriteToolPausesForConfirmation(): void
    {
        $this->repository->method('findMaxPositionFor')->willReturn(null);

        $writeTool = new class implements ToolInterface {
            public int $callCount = 0;

            public function getName(): string
            {
                return 'write_thing';
            }

            public function requiresConfirmation(): bool
            {
                return true;
            }

            public function getDescription(): string
            {
                return '';
            }

            public function getParameterSchema(): array
            {
                return ['type' => 'object', 'properties' => []];
            }

            public function execute(array $arguments, CoreUserInterface $user): string
            {
                ++$this->callCount;

                return 'wrote';
            }
        };

        $chatClient = new class($writeTool) implements ChatClientInterface {
            public int $turn = 0;

            public function __construct(private readonly ToolInterface $tool) {}

            public function getModel(): string
            {
                return 'm';
            }

            public function chat(array $messages, array $tools = []): array
            {
                ++$this->turn;
                if (1 === $this->turn) {
                    return [
                        'role' => 'assistant',
                        'content' => '',
                        'tool_calls' => [[
                            'id' => 'call_w',
                            'function' => ['name' => $this->tool->getName(), 'arguments' => []],
                        ]],
                    ];
                }

                return ['role' => 'assistant', 'content' => 'done', 'tool_calls' => null];
            }
        };

        $manager = $this->makeManager(chatClient: $chatClient, tools: [$writeTool]);

        $conversation = new Conversation();
        $conversation->setUser($this->makeUser());

        $manager->sendMessage($conversation, new MessageInput(content: 'write please'));

        $msgs = $conversation->getMessages()->toArray();
        self::assertCount(2, $msgs, 'pauses before executing write tool');
        self::assertTrue($msgs[1]->isAwaitingConfirmation());
        self::assertSame(0, $writeTool->callCount, 'tool not executed before approval');

        $manager->resumeAfterConfirmation($conversation, ['call_w' => 'approve']);

        $msgs = $conversation->getMessages()->toArray();
        self::assertSame(['user', 'assistant', 'tool', 'assistant'], array_map(static fn ($m) => $m->getRole()->value, $msgs));
        self::assertFalse($msgs[1]->isAwaitingConfirmation());
        self::assertSame(1, $writeTool->callCount);
        self::assertStringContainsString('wrote', $msgs[2]->getContent());
    }

    public function testRejectedToolEmitsRejectionMessage(): void
    {
        $this->repository->method('findMaxPositionFor')->willReturn(null);

        $writeTool = new class implements ToolInterface {
            public int $callCount = 0;

            public function getName(): string
            {
                return 'danger';
            }

            public function requiresConfirmation(): bool
            {
                return true;
            }

            public function getDescription(): string
            {
                return '';
            }

            public function getParameterSchema(): array
            {
                return ['type' => 'object', 'properties' => []];
            }

            public function execute(array $arguments, CoreUserInterface $user): string
            {
                ++$this->callCount;

                return 'should not run';
            }
        };

        $chatClient = new class($writeTool) implements ChatClientInterface {
            public int $turn = 0;

            public function __construct(private readonly ToolInterface $tool) {}

            public function getModel(): string
            {
                return 'm';
            }

            public function chat(array $messages, array $tools = []): array
            {
                ++$this->turn;
                if (1 === $this->turn) {
                    return [
                        'role' => 'assistant',
                        'content' => '',
                        'tool_calls' => [['id' => 'c1', 'function' => ['name' => $this->tool->getName(), 'arguments' => []]]],
                    ];
                }

                return ['role' => 'assistant', 'content' => 'okay, skipping.', 'tool_calls' => null];
            }
        };

        $manager = $this->makeManager(chatClient: $chatClient, tools: [$writeTool]);

        $conversation = new Conversation();
        $conversation->setUser($this->makeUser());

        $manager->sendMessage($conversation, new MessageInput(content: 'do the thing'));
        $manager->resumeAfterConfirmation($conversation, ['c1' => 'reject']);

        $msgs = $conversation->getMessages()->toArray();
        self::assertSame(0, $writeTool->callCount);
        self::assertStringContainsString('rejected', $msgs[2]->getContent());
    }

    public function testEmptyMessageThrows(): void
    {
        $manager = $this->makeManager(chatClient: $this->stubChatClient());

        $this->expectException(RuntimeException::class);
        $manager->sendMessage(new Conversation(), new MessageInput(content: '   '));
    }

    /** @param list<ToolInterface> $tools */
    private function makeManager(ChatClientInterface $chatClient, array $tools = []): ConversationManager
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $auditLogger = new AuditLogger(
            $this->entityManager,
            $security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        $settingRepository = $this->createStub(SettingRepository::class);
        $settingRepository->method('get')->willReturn(null);
        $settings = new AssistantSettings($settingRepository, 'test-model', 60, 4096, 'test-vision', 'ollama');

        $mountPointRepository = $this->createStub(AssistantMountPointRepository::class);
        $mountPointRepository->method('findActiveForUser')->willReturn([]);

        return new ConversationManager(
            $this->entityManager,
            $this->repository,
            $chatClient,
            new ToolRegistry($tools),
            $auditLogger,
            $settings,
            $mountPointRepository,
        );
    }

    private function stubChatClient(string $reply = 'ok', string $model = 'test'): ChatClientInterface
    {
        return new class($reply, $model) implements ChatClientInterface {
            public function __construct(private readonly string $reply, private readonly string $model) {}

            public function getModel(): string
            {
                return $this->model;
            }

            public function chat(array $messages, array $tools = []): array
            {
                return ['role' => 'assistant', 'content' => $this->reply, 'tool_calls' => null];
            }
        };
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        $reflection = new ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }
}
