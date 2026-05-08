<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Planning\Event\Enum\PlanningEventStatusEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PlanningEventInputFactoryInterface::class)]
class PlanningEventInputFactory implements PlanningEventInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PlanningEventInputInterface
    {
        return new PlanningEventInput(
            planningId: isset($data['planningId']) ? (int) $data['planningId'] : 0,
            title: Str::trimFromArray($data, 'title'),
            description: Str::trimOrNullFromArray($data, 'description'),
            location: Str::trimOrNullFromArray($data, 'location'),
            startAt: Str::trimFromArray($data, 'startAt'),
            endAt: Str::trimFromArray($data, 'endAt'),
            allDay: isset($data['allDay']) && (bool) $data['allDay'],
            status: isset($data['status']) && '' !== $data['status']
                ? (string) $data['status']
                : PlanningEventStatusEnum::Confirmed->value,
            attendeeIds: $this->normalizeIdList($data['attendeeIds'] ?? []),
        );
    }

    /** @return list<int> */
    protected function normalizeIdList(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            if (null === $value) {
                continue;
            }
            if ('' === $value) {
                continue;
            }

            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
