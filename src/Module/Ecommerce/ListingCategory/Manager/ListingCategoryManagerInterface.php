<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Manager;

use Aurora\Module\Ecommerce\ListingCategory\Dto\ListingCategoryInputInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;

interface ListingCategoryManagerInterface
{
    public function create(ListingCategoryInputInterface $input): ListingCategoryInterface;

    public function update(ListingCategoryInterface $category, ListingCategoryInputInterface $input): void;

    public function delete(ListingCategoryInterface $category): void;

    public function move(ListingCategoryInterface $category, ?ListingCategoryInterface $newParent, int $position): void;
}
