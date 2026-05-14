<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Crm\Company\Entity\CompanyInterface as CrmCompany;
use Aurora\Module\Crm\Contact\Entity\ContactInterface as CrmContact;
use Aurora\Module\Crm\Deal\Entity\DealInterface as CrmDeal;
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

    /** @return Collection<int, CrmContact> */
    public function getCrmContacts(): Collection;

    public function addCrmContact(CrmContact $contact): static;

    public function removeCrmContact(CrmContact $contact): static;

    public function getCrmCompany(): ?CrmCompany;

    public function setCrmCompany(?CrmCompany $crmCompany): static;

    public function getCrmDeal(): ?CrmDeal;

    public function setCrmDeal(?CrmDeal $crmDeal): static;

    /** @return Collection<int, ProjectTaskInterface> */
    public function getTasks(): Collection;

    /** @return Collection<int, ProjectColumnInterface> */
    public function getColumns(): Collection;

    public function addColumn(ProjectColumnInterface $column): static;
}
