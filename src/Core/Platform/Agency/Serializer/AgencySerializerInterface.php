<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Agency\Serializer;

use Aurora\Core\Platform\Agency\Entity\AgencyInterface;

interface AgencySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(AgencyInterface $agency): array;
}
