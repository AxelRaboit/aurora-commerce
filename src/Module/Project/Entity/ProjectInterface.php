<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;

interface ProjectInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getTitle(): string;

    public function setTitle(string $title): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getStatus(): ProjectStatusEnum;

    public function setStatus(ProjectStatusEnum $status): static;

    public function getStartDate(): ?DateTimeImmutable;

    public function setStartDate(?DateTimeImmutable $startDate): static;

    public function getEndDate(): ?DateTimeImmutable;

    public function setEndDate(?DateTimeImmutable $endDate): static;

    public function getResponsibleUser(): ?CoreUserInterface;

    public function setResponsibleUser(?CoreUserInterface $responsibleUser): static;

    /** @return list<int> */
    public function getCrmContactIds(): array;

    /** @param list<int> $crmContactIds */
    public function setCrmContactIds(array $crmContactIds): static;

    public function getCrmCompanyId(): ?int;

    public function setCrmCompanyId(?int $crmCompanyId): static;

    public function getCrmDealId(): ?int;

    public function setCrmDealId(?int $crmDealId): static;

    /** @return Collection<int, ProjectTaskInterface> */
    public function getTasks(): Collection;

    /** @return Collection<int, ProjectColumnInterface> */
    public function getColumns(): Collection;

    public function addColumn(ProjectColumnInterface $column): static;
}
