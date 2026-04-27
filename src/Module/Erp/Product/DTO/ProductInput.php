<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\DTO;

use Aurora\Core\Support\Str;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProductInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'erp.products.errors.name_required')]
        #[Assert\Length(max: 150)]
        public string $name = '',
        #[Assert\Length(max: 64)]
        #[Assert\Regex(pattern: '/^[A-Za-z0-9_-]+$/', message: 'erp.products.errors.sku_invalid')]
        public ?string $sku = null,
        public ?string $description = null,
        #[Assert\PositiveOrZero(message: 'erp.products.errors.price_invalid')]
        public ?int $priceCents = null,
        public CurrencyEnum $currency = CurrencyEnum::EUR,
        public ProductStatusEnum $status = ProductStatusEnum::Draft,
        public ProductTypeEnum $type = ProductTypeEnum::Physical,
        public ?int $imageId = null,
        #[Assert\PositiveOrZero(message: 'erp.products.errors.stock_invalid')]
        public ?int $stockQuantity = null,
    ) {}

    public static function fromArray(array $data): self
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

        return new self(
            name: Str::trimFromArray($data, 'name'),
            sku: Str::trimOrNullFromArray($data, 'sku'),
            description: Str::trimOrNullFromArray($data, 'description'),
            priceCents: self::extractPriceCents($data, $currency),
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
     */
    private static function extractPriceCents(array $data, CurrencyEnum $currency): ?int
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
