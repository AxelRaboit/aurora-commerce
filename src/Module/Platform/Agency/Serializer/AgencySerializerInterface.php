<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Agency\Serializer;

use Aurora\Module\Platform\Agency\Entity\AgencyInterface;

interface AgencySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(AgencyInterface $agency): array;
}
