<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Serializer;

use Aurora\Module\Crm\Deal\Entity\DealInterface;

interface DealSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(DealInterface $deal): array;
}
