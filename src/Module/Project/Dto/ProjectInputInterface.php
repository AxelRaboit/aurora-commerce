<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Module\Project\Enum\ProjectStatusEnum;

interface ProjectInputInterface
{
    public function getTitle(): string;

    public function getDescription(): ?string;

    public function getStatus(): string;

    public function getStatusEnum(): ProjectStatusEnum;

    public function getStartDate(): ?string;

    public function getEndDate(): ?string;

    public function getResponsibleUserId(): ?int;

    /** @return list<int> */
    public function getCrmContactIds(): array;

    public function getCrmCompanyId(): ?int;

    public function getCrmDealId(): ?int;
}
