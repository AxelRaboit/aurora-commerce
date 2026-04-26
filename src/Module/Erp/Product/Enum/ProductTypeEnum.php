<?php

declare(strict_types=1);

namespace App\Module\Erp\Product\Enum;

enum ProductTypeEnum: string
{
    case Physical = 'physical';
    case Digital = 'digital';
    case Service = 'service';

    /** Whether a product of this type needs a shipping address at checkout and a shipped/delivered workflow. */
    public function requiresShipping(): bool
    {
        return self::Physical === $this;
    }

    /** Whether stock counting makes sense for this type. Digital with finite licences is still possible (stockQuantity > 0). */
    public function tracksStockByDefault(): bool
    {
        return self::Physical === $this;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
