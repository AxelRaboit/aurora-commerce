<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Message;

/**
 * Async work item — carries only the OcrJob id so the payload stays minimal
 * and the handler always reads the latest DB state.
 */
final readonly class ProcessOcrJobMessage
{
    public function __construct(public int $ocrJobId) {}
}
