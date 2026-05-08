<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Serializer;

use Aurora\Module\Planning\Event\Entity\PlanningEventInterface;

interface PlanningEventSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PlanningEventInterface $event): array;
}
