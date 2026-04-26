<?php

declare(strict_types=1);

namespace App\Module\Crm\Deal\DTO;

use App\Core\Support\Str;
use App\Module\Crm\Deal\Enum\DealStageEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DealInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'crm.deals.errors.name_required')]
        #[Assert\Length(max: 200)]
        public string $name = '',
        #[Assert\NotNull]
        public DealStageEnum $stage = DealStageEnum::Lead,
        public ?string $value = null,
        #[Assert\Positive]
        public ?int $contactId = null,
        #[Assert\Positive]
        public ?int $companyId = null,
        public ?string $closingDate = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $stage = DealStageEnum::Lead;
        if (isset($data['stage']) && '' !== $data['stage']) {
            $stage = DealStageEnum::tryFrom($data['stage']) ?? DealStageEnum::Lead;
        }

        return new self(
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
