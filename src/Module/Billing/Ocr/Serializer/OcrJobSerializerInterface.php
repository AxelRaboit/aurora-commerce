<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Serializer;

use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;

interface OcrJobSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(OcrJobInterface $job): array;
}
