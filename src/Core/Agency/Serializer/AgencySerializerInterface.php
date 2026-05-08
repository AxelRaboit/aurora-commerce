<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\Serializer;

use Aurora\Core\Agency\Entity\AgencyInterface;

interface AgencySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(AgencyInterface $agency): array;
}
