<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Vision\Service;

use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Module\Assistant\Setting\AssistantSettings;
use Aurora\Module\Assistant\Vision\Contract\VisionDescriberInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function is_string;
use function sprintf;

/**
 * Free-text image description against the local Ollama vision model.
 * Distinct from {@see OllamaVisionClient}
 * — that one forces structured JSON output for invoice extraction; here
 * we want prose the chat model can splice into its next turn.
 *
 * Shares the same Ollama endpoint (env ASSISTANT_OLLAMA_URL → env
 * OLLAMA_URL by default) but reads the vision model name through
 * {@see AssistantSettings} so an admin can switch models from /backend/
 * settings without redeploying.
 */
#[AsAlias(VisionDescriberInterface::class)]
final readonly class OllamaVisionDescriber implements VisionDescriberInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private AssistantSettings $settings,
        private string $baseUrl,
    ) {}

    public function getModel(): string
    {
        return $this->settings->getVisionModel();
    }

    public function describe(string $imageAbsolutePath, string $prompt): string
    {
        if (!is_file($imageAbsolutePath) || !is_readable($imageAbsolutePath)) {
            throw new RuntimeException(sprintf('Image not readable: %s', $imageAbsolutePath));
        }

        $imageBytes = file_get_contents($imageAbsolutePath);
        if (false === $imageBytes) {
            throw new RuntimeException('Failed to read image bytes.');
        }

        $timeout = $this->settings->getHttpTimeout();
        $body = [
            'model' => $this->settings->getVisionModel(),
            'prompt' => $prompt,
            'images' => [base64_encode($imageBytes)],
            'stream' => false,
            'think' => false,
            'options' => [
                'temperature' => 0.2,
                'num_ctx' => $this->settings->getNumCtx(),
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', mb_rtrim($this->baseUrl, '/').'/api/generate', [
                'json' => $body,
                'timeout' => $timeout,
                'max_duration' => $timeout * 5,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= HttpStatusEnum::BadRequest->value) {
                throw new RuntimeException(sprintf('Ollama HTTP %d: %s', $statusCode, $response->getContent(false)));
            }

            $raw = $response->toArray(false);
        } catch (TransportException $transportException) {
            throw new RuntimeException('Ollama transport error: '.$transportException->getMessage(), 0, $transportException);
        }

        if (!isset($raw['response']) || !is_string($raw['response'])) {
            throw new RuntimeException('Ollama response missing "response" string.');
        }

        $text = preg_replace('/<think>.*?<\/think>/s', '', $raw['response']) ?? $raw['response'];

        return mb_trim($text);
    }
}
