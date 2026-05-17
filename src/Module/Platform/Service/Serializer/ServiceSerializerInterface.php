<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Service\Serializer;

use Aurora\Module\Platform\Service\Entity\ServiceInterface;

interface ServiceSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ServiceInterface $service): array;
}
