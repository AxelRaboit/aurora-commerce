<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ListingInputFactoryInterface::class)]
class ListingInputFactory implements ListingInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ListingInputInterface
    {
        return new ListingInput(
            productId: isset($data['productId']) && '' !== (string) $data['productId'] ? (int) $data['productId'] : null,
            slug: Str::trimFromArray($data, 'slug'),
            marketingTitle: Str::trimOrNullFromArray($data, 'marketingTitle'),
            marketingDescription: Str::trimOrNullFromArray($data, 'marketingDescription'),
            featuredImageId: isset($data['featuredImageId']) && '' !== (string) $data['featuredImageId'] ? (int) $data['featuredImageId'] : null,
            isVisibleOnShop: (bool) ($data['isVisibleOnShop'] ?? true),
            seoTitle: Str::trimOrNullFromArray($data, 'seoTitle'),
            seoDescription: Str::trimOrNullFromArray($data, 'seoDescription'),
            categoryIds: $this->extractCategoryIds($data),
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<int>
     */
    private function extractCategoryIds(array $data): array
    {
        $raw = $data['categoryIds'] ?? $data['category_ids'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            if (is_numeric($value)) {
                $id = (int) $value;
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
