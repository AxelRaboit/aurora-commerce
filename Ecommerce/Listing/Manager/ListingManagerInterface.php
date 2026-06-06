<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Manager;

use Aurora\Module\Ecommerce\Listing\Dto\ListingInputInterface;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;

interface ListingManagerInterface
{
    public function create(ListingInputInterface $input): ListingInterface;

    public function update(ListingInterface $listing, ListingInputInterface $input): void;

    public function delete(ListingInterface $listing): void;
}
