<?php

declare(strict_types=1);

namespace App\Module\Erp\Product\Service;

final readonly class SkuGenerator
{
    private const string PREFIX = 'PROD';

    private const int PAD_LENGTH = 6;

    public function generate(int $id): string
    {
        return sprintf('%s-%s', self::PREFIX, mb_str_pad((string) $id, self::PAD_LENGTH, '0', STR_PAD_LEFT));
    }
}
