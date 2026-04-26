<?php

declare(strict_types=1);

namespace App\Module\Ecommerce\Listing\Contract;

use App\Module\Ecommerce\Listing\DTO\ListingInput;
use App\Module\Ecommerce\Listing\Entity\Listing;

interface ListingManagerInterface
{
    public function create(ListingInput $input): Listing;

    public function update(Listing $listing, ListingInput $input): void;

    public function delete(Listing $listing): void;
}
