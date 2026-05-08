<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectInputFactoryInterface::class)]
class ProjectInputFactory implements ProjectInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ProjectInputInterface
    {
        return new ProjectInput(
            title: Str::trimFromArray($data, 'title'),
            description: Str::trimOrNullFromArray($data, 'description'),
            status: isset($data['status']) && '' !== $data['status']
                ? (string) $data['status']
                : ProjectStatusEnum::Draft->value,
            startDate: Str::trimOrNullFromArray($data, 'startDate'),
            endDate: Str::trimOrNullFromArray($data, 'endDate'),
            responsibleUserId: isset($data['responsibleUserId']) && '' !== (string) $data['responsibleUserId'] ? (int) $data['responsibleUserId'] : null,
            crmContactIds: $this->normalizeIdList($data['crmContactIds'] ?? []),
            crmCompanyId: isset($data['crmCompanyId']) && '' !== (string) $data['crmCompanyId'] ? (int) $data['crmCompanyId'] : null,
            crmDealId: isset($data['crmDealId']) && '' !== (string) $data['crmDealId'] ? (int) $data['crmDealId'] : null,
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
            if (null === $value || '' === $value) {
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
