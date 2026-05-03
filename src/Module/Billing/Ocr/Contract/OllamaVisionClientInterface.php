<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Contract;

interface OllamaVisionClientInterface
{
    public function getModel(): string;

    /**
     * @param array<string, mixed> $jsonSchema
     *
     * @return array{response: array<string, mixed>, raw: array<string, mixed>}
     *
     * @throws \RuntimeException on transport, HTTP, or decoding errors
     */
    public function generateStructured(string $prompt, string $imageAbsolutePath, array $jsonSchema): array;
}
