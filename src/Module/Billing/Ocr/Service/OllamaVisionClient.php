<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Service;

use Aurora\Module\Billing\Ocr\Contract\OllamaVisionClientInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function is_array;
use function is_string;
use function sprintf;

/**
 * Client for the local Ollama instance (vision-capable model).
 *
 * Sends an image + prompt to /api/generate with a JSON schema in the `format`
 * field — Ollama enforces the schema during decoding so we always get a parseable
 * payload back. Use generateStructured() for invoice extraction.
 */
#[AsAlias(OllamaVisionClientInterface::class)]
final readonly class OllamaVisionClient implements OllamaVisionClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
        private string $model,
        private int $timeout,
    ) {}

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @param array<string, mixed> $jsonSchema JSON Schema describing the expected response shape
     *
     * @return array{response: array<string, mixed>, raw: array<string, mixed>}
     *
     * @throws RuntimeException on transport, HTTP, or decoding errors
     */
    public function generateStructured(string $prompt, string $imageAbsolutePath, array $jsonSchema): array
    {
        if (!is_file($imageAbsolutePath) || !is_readable($imageAbsolutePath)) {
            throw new RuntimeException(sprintf('Ollama image not readable: %s', $imageAbsolutePath));
        }

        $imageBytes = file_get_contents($imageAbsolutePath);
        if (false === $imageBytes) {
            throw new RuntimeException('Failed to read image for Ollama');
        }

        // NB: We deliberately do NOT pass `format` to Ollama. Some vision models
        // (e.g. qwen3-vl) return empty responses when a JSON schema is set as
        // the format constraint. We rely on prompt + a tolerant parser instead.
        // The schema is appended to the prompt so the model still has the shape.
        //
        // `think: false` disables the chain-of-thought reasoning block on qwen3
        // thinking models — without it the <think>...</think> tokens fill the
        // entire context window and the actual JSON response is empty.
        $body = [
            'model' => $this->model,
            'prompt' => $prompt."\n\nJSON Schema (your response MUST conform):\n".json_encode($jsonSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'images' => [base64_encode($imageBytes)],
            'stream' => false,
            'think' => false,
            'options' => [
                'temperature' => 0.1,
                'num_ctx' => 16384,
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', mb_rtrim($this->baseUrl, '/').'/api/generate', [
                'json' => $body,
                // Idle timeout (between bytes). With stream:false Ollama buffers
                // the whole completion, so any local model slower than `timeout`s
                // would otherwise be killed mid-generation.
                'timeout' => $this->timeout,
                // Total request budget — generous for big local models on CPU.
                'max_duration' => $this->timeout * 5,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= Response::HTTP_BAD_REQUEST) {
                throw new RuntimeException(sprintf('Ollama HTTP %d: %s', $statusCode, $response->getContent(false)));
            }

            $raw = $response->toArray(false);
        } catch (TransportException $transportException) {
            throw new RuntimeException('Ollama transport error: '.$transportException->getMessage(), 0, $transportException);
        }

        if (!isset($raw['response']) || !is_string($raw['response'])) {
            throw new RuntimeException('Ollama response missing "response" string');
        }

        $decoded = $this->decodeJsonLoose($raw['response']);
        if (!is_array($decoded)) {
            $preview = mb_substr($raw['response'], 0, 200);
            throw new RuntimeException('Ollama returned non-JSON payload despite schema. First 200 chars: '.$preview);
        }

        return ['response' => $decoded, 'raw' => $raw];
    }

    /**
     * Some models wrap JSON in ```json``` fences, prepend prose, or emit
     * <think>...</think> reasoning blocks (e.g. qwen3) before the payload.
     * Try strict decode first, then progressively looser strategies.
     */
    private function decodeJsonLoose(string $payload): mixed
    {
        $payload = mb_trim($payload);

        // Strip <think>...</think> reasoning blocks emitted by thinking models.
        $payload = preg_replace('/<think>.*?<\/think>/s', '', $payload) ?? $payload;
        $payload = mb_trim($payload);

        $decoded = json_decode($payload, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Strip Markdown fences ```json ... ```
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $payload, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Walk forward from the first '{' counting depth to find the matching '}'.
        $firstBrace = mb_strpos($payload, '{');
        if (false !== $firstBrace) {
            $depth = 0;
            $inString = false;
            $escape = false;
            $len = mb_strlen($payload);
            for ($i = $firstBrace; $i < $len; ++$i) {
                $char = mb_substr($payload, $i, 1);
                if ($escape) { $escape = false; continue; }
                if ($char === '\\' && $inString) { $escape = true; continue; }
                if ($char === '"') { $inString = !$inString; continue; }
                if ($inString) { continue; }
                if ($char === '{') { ++$depth; }
                elseif ($char === '}') {
                    --$depth;
                    if ($depth === 0) {
                        $decoded = json_decode(mb_substr($payload, $firstBrace, $i - $firstBrace + 1), true);
                        if (is_array($decoded)) {
                            return $decoded;
                        }
                        break;
                    }
                }
            }
        }

        return null;
    }
}
