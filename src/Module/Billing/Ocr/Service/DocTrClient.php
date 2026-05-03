<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Service;

use Aurora\Module\Billing\Ocr\Contract\DocTrClientInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function is_array;
use function sprintf;

/**
 * Client for the docTR Python microservice (text + layout extraction).
 *
 * The microservice exposes POST /extract accepting a multipart upload of
 * the original document and returning a structured JSON payload:
 * { pages: [{ width, height, text, blocks: [{text, bbox, confidence}] }], text }.
 */
#[AsAlias(DocTrClientInterface::class)]
final readonly class DocTrClient implements DocTrClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private Filesystem $filesystem,
        private string $baseUrl,
        private int $timeout,
    ) {}

    /**
     * Render one page of a PDF (or image) to a PNG file on disk and return its
     * absolute path. For images this is essentially a copy. Used to feed Ollama
     * which only accepts images, not PDFs.
     *
     * @throws RuntimeException on transport, HTTP, or filesystem errors
     */
    public function renderToPng(string $absolutePath, string $destinationPath, int $dpi = 200): string
    {
        $formData = new FormDataPart([
            'file' => DataPart::fromPath($absolutePath),
            'dpi' => (string) $dpi,
        ]);

        try {
            $response = $this->httpClient->request('POST', mb_rtrim($this->baseUrl, '/').'/render', [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable(),
                'timeout' => $this->timeout,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= Response::HTTP_BAD_REQUEST) {
                throw new RuntimeException(sprintf('docTR render HTTP %d: %s', $statusCode, $response->getContent(false)));
            }

            $bytes = $response->getContent(false);
        } catch (TransportException $transportException) {
            throw new RuntimeException('docTR transport error: '.$transportException->getMessage(), 0, $transportException);
        }

        try {
            // dumpFile() atomically writes and creates the parent directory if missing.
            $this->filesystem->dumpFile($destinationPath, $bytes);
        } catch (IOException $ioException) {
            throw new RuntimeException(sprintf('Cannot write rendered PNG to %s: %s', $destinationPath, $ioException->getMessage()), $ioException->getCode(), previous: $ioException);
        }

        return $destinationPath;
    }

    /**
     * @return array{pages: list<array<string, mixed>>, text: string}
     *
     * @throws RuntimeException on transport, HTTP, or decoding errors
     */
    public function extract(string $absolutePath): array
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            throw new RuntimeException(sprintf('docTR input not readable: %s', $absolutePath));
        }

        $formData = new FormDataPart([
            'file' => DataPart::fromPath($absolutePath),
        ]);

        try {
            $response = $this->httpClient->request('POST', mb_rtrim($this->baseUrl, '/').'/extract', [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable(),
                'timeout' => $this->timeout,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= Response::HTTP_BAD_REQUEST) {
                throw new RuntimeException(sprintf('docTR HTTP %d: %s', $statusCode, $response->getContent(false)));
            }

            $payload = $response->toArray(false);
        } catch (TransportException $transportException) {
            throw new RuntimeException('docTR transport error: '.$transportException->getMessage(), 0, $transportException);
        }

        if (!isset($payload['pages']) || !is_array($payload['pages'])) {
            throw new RuntimeException('docTR response missing "pages"');
        }

        return [
            'pages' => $payload['pages'],
            'text' => $payload['text'] ?? '',
        ];
    }
}
