<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\DTO;

use Aurora\Core\Support\Str;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DealInputFactoryInterface::class)]
class DealInputFactory implements DealInputFactoryInterface
{
    public function fromArray(array $data): DealInputInterface
    {
        $stage = DealStageEnum::Lead;
        if (isset($data['stage']) && '' !== $data['stage']) {
            $stage = DealStageEnum::tryFrom($data['stage']) ?? DealStageEnum::Lead;
        }

        return new DealInput(
            name: Str::trimFromArray($data, 'name'),
            stage: $stage,
            value: Str::trimOrNullFromArray($data, 'value'),
            contactId: isset($data['contactId']) && '' !== (string) $data['contactId'] ? (int) $data['contactId'] : null,
            companyId: isset($data['companyId']) && '' !== (string) $data['companyId'] ? (int) $data['companyId'] : null,
            closingDate: Str::trimOrNullFromArray($data, 'closingDate'),
            notes: Str::trimOrNullFromArray($data, 'notes'),
        );
    }
}
