<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Planning\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Planning\Planning\Enum\PlanningVisibilityEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PlanningInputFactoryInterface::class)]
class PlanningInputFactory implements PlanningInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PlanningInputInterface
    {
        return new PlanningInput(
            name: Str::trimFromArray($data, 'name'),
            description: Str::trimOrNullFromArray($data, 'description'),
            color: isset($data['color']) && '' !== $data['color']
                ? (string) $data['color']
                : '#3b82f6',
            timezone: isset($data['timezone']) && '' !== $data['timezone']
                ? (string) $data['timezone']
                : 'Europe/Paris',
            visibility: isset($data['visibility']) && '' !== $data['visibility']
                ? (string) $data['visibility']
                : PlanningVisibilityEnum::Private_->value,
            ownerId: isset($data['ownerId']) && '' !== (string) $data['ownerId'] ? (int) $data['ownerId'] : null,
            agencyId: isset($data['agencyId']) && '' !== (string) $data['agencyId'] ? (int) $data['agencyId'] : null,
        );
    }
}
