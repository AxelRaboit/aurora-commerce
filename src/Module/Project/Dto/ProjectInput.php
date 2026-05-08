<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Module\Project\Enum\ProjectStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectInput implements ProjectInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.projects.errors.title_required')]
        #[Assert\Length(max: 255)]
        public readonly string $title = '',
        public readonly ?string $description = null,
        #[Assert\NotBlank(message: 'backend.projects.errors.status_required')]
        #[Assert\Choice(callback: [ProjectStatusEnum::class, 'values'], message: 'backend.projects.errors.status_invalid')]
        public readonly string $status = ProjectStatusEnum::Draft->value,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        #[Assert\Positive]
        public readonly ?int $responsibleUserId = null,
        /** @var list<int> */
        #[Assert\All([new Assert\Positive()])]
        public readonly array $crmContactIds = [],
        #[Assert\Positive]
        public readonly ?int $crmCompanyId = null,
        #[Assert\Positive]
        public readonly ?int $crmDealId = null,
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusEnum(): ProjectStatusEnum
    {
        return ProjectStatusEnum::from($this->status);
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function getResponsibleUserId(): ?int
    {
        return $this->responsibleUserId;
    }

    public function getCrmContactIds(): array
    {
        return $this->crmContactIds;
    }

    public function getCrmCompanyId(): ?int
    {
        return $this->crmCompanyId;
    }

    public function getCrmDealId(): ?int
    {
        return $this->crmDealId;
    }
}
