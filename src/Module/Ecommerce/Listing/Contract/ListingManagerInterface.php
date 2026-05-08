<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Contract;

use Aurora\Module\Ecommerce\Listing\Dto\ListingInput;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;

interface ListingManagerInterface
{
    public function create(ListingInput $input): Listing;

    public function update(Listing $listing, ListingInput $input): void;

    public function delete(Listing $listing): void;
}
