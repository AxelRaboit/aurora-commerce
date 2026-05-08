<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Planning\Serializer;

use Aurora\Module\Planning\Planning\Entity\PlanningInterface;

interface PlanningSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PlanningInterface $planning): array;
}
