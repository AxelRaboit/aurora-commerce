<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\DTO;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ListingInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'ecommerce.listings.errors.product_required')]
        public ?int $productId = null,
        #[Assert\NotBlank(message: 'ecommerce.listings.errors.slug_required')]
        #[Assert\Length(max: 200)]
        #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'ecommerce.listings.errors.slug_invalid')]
        public string $slug = '',
        #[Assert\Length(max: 200)]
        public ?string $marketingTitle = null,
        public ?string $marketingDescription = null,
        public ?int $featuredImageId = null,
        public bool $isVisibleOnShop = true,
        #[Assert\Length(max: 200)]
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: isset($data['productId']) && '' !== (string) $data['productId'] ? (int) $data['productId'] : null,
            slug: Str::trimFromArray($data, 'slug'),
            marketingTitle: Str::trimOrNullFromArray($data, 'marketingTitle'),
            marketingDescription: Str::trimOrNullFromArray($data, 'marketingDescription'),
            featuredImageId: isset($data['featuredImageId']) && '' !== (string) $data['featuredImageId'] ? (int) $data['featuredImageId'] : null,
            isVisibleOnShop: (bool) ($data['isVisibleOnShop'] ?? true),
            seoTitle: Str::trimOrNullFromArray($data, 'seoTitle'),
            seoDescription: Str::trimOrNullFromArray($data, 'seoDescription'),
        );
    }
}
