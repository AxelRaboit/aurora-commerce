<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Llm\Service;

use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Module\Assistant\Llm\Contract\ChatClientInterface;
use Aurora\Module\Assistant\Setting\AssistantSettings;
use RuntimeException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function is_array;
use function is_string;
use function sprintf;

/**
 * Chat client backed by the Anthropic Messages API (claude-* models).
 * Implements the same {@see ChatClientInterface} contract as
 * {@see OllamaChatClient} and normalises the two formats at the boundary:
 *
 *   OpenAI/Ollama ← ConversationManager → OpenAI (internal)
 *                                             ↓ (AnthropicChatClient converts)
 *                                        Anthropic API
 *
 * Notable conversions:
 *   • `role:system` messages → top-level `system` string (Anthropic v1 spec)
 *   • `role:tool` messages → `role:user` with `content:[{type:tool_result}]`
 *   • `role:assistant` with tool_calls → content array with tool_use blocks
 *   • Tool schemas: OpenAI `parameters` → Anthropic `input_schema`
 *   • Response: content array → normalised {role, content, tool_calls} map
 */
final readonly class AnthropicChatClient implements ChatClientInterface
{
    private const string API_URL = 'https://api.anthropic.com/v1/messages';

    private const string API_VERSION = '2023-06-01';

    public function __construct(
        private HttpClientInterface $httpClient,
        private AssistantSettings $settings,
        private string $apiKey,
    ) {}

    public function getModel(): string
    {
        return $this->settings->getChatModel();
    }

    public function chat(array $messages, array $tools = []): array
    {
        if ('' === $this->apiKey) {
            throw new RuntimeException('Anthropic API key is not configured (ANTHROPIC_API_KEY).');
        }

        [$systemPrompt, $anthropicMessages] = $this->convertMessages($messages);
        $anthropicTools = $this->convertTools($tools);

        $body = [
            'model' => $this->settings->getChatModel(),
            'max_tokens' => max(1024, $this->settings->getNumCtx()),
            'messages' => $anthropicMessages,
        ];

        if ('' !== $systemPrompt) {
            $body['system'] = $systemPrompt;
        }

        if ([] !== $anthropicTools) {
            $body['tools'] = $anthropicTools;
        }

        $timeout = $this->settings->getHttpTimeout();

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => self::API_VERSION,
                    'content-type' => 'application/json',
                ],
                'json' => $body,
                'timeout' => $timeout,
                'max_duration' => $timeout * 5,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= HttpStatusEnum::BadRequest->value) {
                throw new RuntimeException(sprintf('Anthropic HTTP %d: %s', $statusCode, $response->getContent(false)));
            }

            $raw = $response->toArray(false);
        } catch (TransportException $transportException) {
            throw new RuntimeException('Anthropic transport error: '.$transportException->getMessage(), 0, $transportException);
        }

        return $this->normaliseResponse($raw);
    }

    // ── Message conversion ────────────────────────────────────────────

    /**
     * @param list<array<string, mixed>> $messages
     *
     * @return array{string, list<array<string, mixed>>}
     */
    private function convertMessages(array $messages): array
    {
        $systemPrompt = '';
        $out = [];

        foreach ($messages as $message) {
            $role = (string) ($message['role'] ?? '');
            $content = (string) ($message['content'] ?? '');

            if ('system' === $role) {
                $systemPrompt = $content;
                continue;
            }

            if ('tool' === $role) {
                // Tool result → role:user with tool_result content block
                $out[] = [
                    'role' => 'user',
                    'content' => [[
                        'type' => 'tool_result',
                        'tool_use_id' => (string) ($message['tool_call_id'] ?? ''),
                        'content' => $content,
                    ]],
                ];
                continue;
            }

            if ('assistant' === $role) {
                $toolCalls = $message['tool_calls'] ?? null;
                if (is_array($toolCalls) && [] !== $toolCalls) {
                    // Build a content array: optional text + tool_use blocks
                    $contentBlocks = [];
                    if ('' !== $content) {
                        $contentBlocks[] = ['type' => 'text', 'text' => $content];
                    }

                    foreach ($toolCalls as $call) {
                        $func = $call['function'] ?? [];
                        $args = is_array($func) && isset($func['arguments']) ? $func['arguments'] : [];
                        $contentBlocks[] = [
                            'type' => 'tool_use',
                            'id' => (string) ($call['id'] ?? uniqid('toolu_', true)),
                            'name' => is_array($func) && isset($func['name']) && is_string($func['name']) ? $func['name'] : '',
                            'input' => is_string($args) ? (json_decode($args, true) ?? []) : (is_array($args) ? $args : []),
                        ];
                    }

                    $out[] = ['role' => 'assistant', 'content' => $contentBlocks];
                    continue;
                }

                $out[] = ['role' => 'assistant', 'content' => $content];
                continue;
            }

            // user messages
            $out[] = ['role' => 'user', 'content' => $content];
        }

        return [$systemPrompt, $out];
    }

    /**
     * Convert OpenAI-style tool schemas to Anthropic's `input_schema` format.
     *
     * @param list<array<string, mixed>> $tools
     *
     * @return list<array<string, mixed>>
     */
    private function convertTools(array $tools): array
    {
        $out = [];
        foreach ($tools as $tool) {
            $func = $tool['function'] ?? [];
            if (!is_array($func)) {
                continue;
            }

            if (!isset($func['name'])) {
                continue;
            }

            $out[] = [
                'name' => (string) $func['name'],
                'description' => (string) ($func['description'] ?? ''),
                'input_schema' => $func['parameters'] ?? ['type' => 'object', 'properties' => []],
            ];
        }

        return $out;
    }

    // ── Response normalisation ────────────────────────────────────────

    /**
     * @param array<string, mixed> $raw
     *
     * @return array{role: string, content: string, tool_calls: list<array<string, mixed>>|null}
     */
    private function normaliseResponse(array $raw): array
    {
        $contentBlocks = $raw['content'] ?? [];
        if (!is_array($contentBlocks)) {
            return ['role' => 'assistant', 'content' => '', 'tool_calls' => null];
        }

        $text = '';
        $toolCalls = [];

        foreach ($contentBlocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? '';

            if ('text' === $type) {
                $text .= (string) ($block['text'] ?? '');
            }

            if ('tool_use' === $type) {
                $toolCalls[] = [
                    'id' => (string) ($block['id'] ?? ''),
                    'function' => [
                        'name' => (string) ($block['name'] ?? ''),
                        'arguments' => $block['input'] ?? [],
                    ],
                ];
            }
        }

        return [
            'role' => 'assistant',
            'content' => $text,
            'tool_calls' => [] !== $toolCalls ? $toolCalls : null,
        ];
    }
}
