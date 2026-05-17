<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Planning\Serializer;

use Aurora\Module\Platform\Agency\Entity\AgencyInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PlanningSerializerInterface::class)]
class PlanningSerializer implements PlanningSerializerInterface
{
    public function serialize(PlanningInterface $planning): array
    {
        return [
            'id' => $planning->getId(),
            'name' => $planning->getName(),
            'description' => $planning->getDescription(),
            'color' => $planning->getColor(),
            'timezone' => $planning->getTimezone(),
            'visibility' => $planning->getVisibility()->value,
            'owner' => $planning->getOwner() instanceof CoreUserInterface ? [
                'id' => $planning->getOwner()->getId(),
                'name' => $planning->getOwner()->getName(),
            ] : null,
            'agency' => $planning->getAgency() instanceof AgencyInterface ? [
                'id' => $planning->getAgency()->getId(),
                'name' => $planning->getAgency()->getName(),
            ] : null,
            'createdAt' => $planning->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $planning->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
