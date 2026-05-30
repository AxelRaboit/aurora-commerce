<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Dto;

use Aurora\Core\Support\Str;
use Aurora\Core\Money\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProductInputFactoryInterface::class)]
class ProductInputFactory implements ProductInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ProductInputInterface
    {
        $currency = CurrencyEnum::EUR;
        if (isset($data['currency'])) {
            $currency = CurrencyEnum::tryFrom(mb_strtoupper(mb_trim((string) $data['currency']))) ?? CurrencyEnum::EUR;
        }

        $status = ProductStatusEnum::Draft;
        if (isset($data['status']) && null !== ProductStatusEnum::tryFrom((string) $data['status'])) {
            $status = ProductStatusEnum::from((string) $data['status']);
        }

        $type = ProductTypeEnum::Physical;
        if (isset($data['type']) && null !== ProductTypeEnum::tryFrom((string) $data['type'])) {
            $type = ProductTypeEnum::from((string) $data['type']);
        }

        return new ProductInput(
            name: Str::trimFromArray($data, 'name'),
            reference: Str::trimOrNullFromArray($data, 'reference'),
            description: Str::trimOrNullFromArray($data, 'description'),
            priceCents: $this->extractPriceCents($data, $currency),
            currency: $currency,
            status: $status,
            type: $type,
            imageId: isset($data['imageId']) && '' !== (string) $data['imageId'] ? (int) $data['imageId'] : null,
            stockQuantity: array_key_exists('stockQuantity', $data) && '' !== (string) $data['stockQuantity'] && null !== $data['stockQuantity'] ? (int) $data['stockQuantity'] : null,
        );
    }

    /**
     * Resolves the price in cents from either:
     *  - `price` (decimal, in major units — e.g. "19.99" or 19.99 → 1999 cents),
     *  - or `priceCents` (legacy/raw int).
     *
     * Accepts comma or dot as decimal separator. Empty/null → null (no price set).
     *
     * @param array<string, mixed> $data
     */
    protected function extractPriceCents(array $data, CurrencyEnum $currency): ?int
    {
        if (array_key_exists('price', $data) && '' !== (string) $data['price'] && null !== $data['price']) {
            $normalized = str_replace(',', '.', (string) $data['price']);
            if (!is_numeric($normalized)) {
                return null;
            }

            return (int) round(((float) $normalized) * (10 ** $currency->decimals()));
        }

        if (array_key_exists('priceCents', $data) && '' !== (string) $data['priceCents'] && null !== $data['priceCents']) {
            return (int) $data['priceCents'];
        }

        return null;
    }
}
