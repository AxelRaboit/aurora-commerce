<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Service\Serializer;

use Aurora\Core\Platform\Service\Entity\ServiceInterface;

interface ServiceSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ServiceInterface $service): array;
}
