<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Planning\View;

use Aurora\Module\Planning\Planning\Repository\PlanningRepository;
use Aurora\Module\Planning\Planning\Serializer\PlanningSerializerInterface;

class PlanningsViewBuilder
{
    public function __construct(
        protected readonly PlanningRepository $planningRepository,
        protected readonly PlanningSerializerInterface $planningSerializer,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(): array
    {
        return [
            'plannings' => array_map(
                $this->planningSerializer->serialize(...),
                $this->planningRepository->findAllOrderedByName(),
            ),
        ];
    }
}
