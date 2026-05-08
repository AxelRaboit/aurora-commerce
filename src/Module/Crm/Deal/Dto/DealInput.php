<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Dto;

use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Symfony\Component\Validator\Constraints as Assert;

readonly class DealInput implements DealInputInterface
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getStage(): DealStageEnum
    {
        return $this->stage;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getContactId(): ?int
    {
        return $this->contactId;
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function getClosingDate(): ?string
    {
        return $this->closingDate;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }
}
