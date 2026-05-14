<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Manager;

use Aurora\Module\Ecommerce\ListingTag\Dto\ListingTagInputInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;

interface ListingTagManagerInterface
{
    public function create(ListingTagInputInterface $input): ListingTagInterface;

    public function update(ListingTagInterface $tag, ListingTagInputInterface $input): void;

    public function delete(ListingTagInterface $tag): void;
}
