<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Llm\Service;

use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Module\Assistant\Llm\Contract\ChatClientInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function is_array;
use function is_string;
use function sprintf;

/**
 * Chat client for the local Ollama instance. Mirrors `OllamaVisionClient`
 * but targets `/api/chat` (multi-turn, tools-aware) instead of `/api/generate`.
 *
 * Uses `stream: false` for the initial Phase 1A roundtrip — streaming is
 * deferred to Phase 1B. `think: false` disables the chain-of-thought block
 * on qwen3 thinking models so the response payload isn't crowded out by
 * <think>...</think> tokens.
 */
#[AsAlias(ChatClientInterface::class)]
final readonly class OllamaChatClient implements ChatClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
        private string $model,
        private int $timeout,
        private int $numCtx = 8192,
    ) {}

    public function getModel(): string
    {
        return $this->model;
    }

    public function chat(array $messages, array $tools = []): array
    {
        $body = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => false,
            'think' => false,
            'options' => [
                'temperature' => 0.7,
                'num_ctx' => $this->numCtx,
            ],
        ];

        if ([] !== $tools) {
            $body['tools'] = $tools;
        }

        try {
            $response = $this->httpClient->request('POST', mb_rtrim($this->baseUrl, '/').'/api/chat', [
                'json' => $body,
                'timeout' => $this->timeout,
                'max_duration' => $this->timeout * 5,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= HttpStatusEnum::BadRequest->value) {
                throw new RuntimeException(sprintf('Ollama HTTP %d: %s', $statusCode, $response->getContent(false)));
            }

            $raw = $response->toArray(false);
        } catch (TransportException $transportException) {
            throw new RuntimeException('Ollama transport error: '.$transportException->getMessage(), 0, $transportException);
        }

        $message = $raw['message'] ?? null;
        if (!is_array($message)) {
            throw new RuntimeException('Ollama response missing "message" object');
        }

        $content = $message['content'] ?? '';
        if (!is_string($content)) {
            $content = '';
        }

        // Strip <think>...</think> reasoning blocks if the model emitted them
        // despite think:false — defensive, some local builds ignore the flag.
        $content = preg_replace('/<think>.*?<\/think>/s', '', $content) ?? $content;
        $content = mb_trim($content);

        $toolCalls = null;
        if (isset($message['tool_calls']) && is_array($message['tool_calls']) && [] !== $message['tool_calls']) {
            $toolCalls = array_values($message['tool_calls']);
        }

        return [
            'role' => 'assistant',
            'content' => $content,
            'tool_calls' => $toolCalls,
        ];
    }
}
