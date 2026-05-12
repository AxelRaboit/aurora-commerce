<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Serializer;

use Aurora\Module\Billing\Invoice\Entity\TiersInterface;

interface TiersSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(TiersInterface $tiers): array;
}
