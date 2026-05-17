<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Crm\Company\Entity\CompanyInterface as CrmCompany;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\Deal\Entity\DealInterface as CrmDeal;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractProject implements ProjectInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(length: 255)]
    protected string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(length: 20, enumType: ProjectStatusEnum::class, options: ['default' => 'draft'])]
    protected ProjectStatusEnum $status = ProjectStatusEnum::Draft;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $endDate = null;

    #[ORM\ManyToOne(targetEntity: CoreUserInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?CoreUserInterface $responsibleUser = null;

    /** @var Collection<int, ContactInterface> */
    protected Collection $crmContacts;

    #[ORM\ManyToOne(targetEntity: CrmCompany::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?CrmCompany $crmCompany = null;

    #[ORM\ManyToOne(targetEntity: CrmDeal::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?CrmDeal $crmDeal = null;

    /** @var Collection<int, ProjectTaskInterface> */
    #[ORM\OneToMany(targetEntity: ProjectTaskInterface::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'createdAt' => 'ASC'])]
    protected Collection $tasks;

    /** @var Collection<int, ProjectColumnInterface> */
    #[ORM\OneToMany(targetEntity: ProjectColumnInterface::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $columns;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->crmContacts = new ArrayCollection();
        $this->columns = new ArrayCollection();
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ProjectStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ProjectStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getResponsibleUser(): ?CoreUserInterface
    {
        return $this->responsibleUser;
    }

    public function setResponsibleUser(?CoreUserInterface $responsibleUser): static
    {
        $this->responsibleUser = $responsibleUser;

        return $this;
    }

    public function getCrmContacts(): Collection
    {
        return $this->crmContacts;
    }

    public function addCrmContact(ContactInterface $contact): static
    {
        if (!$this->crmContacts->contains($contact)) {
            $this->crmContacts->add($contact);
        }

        return $this;
    }

    public function removeCrmContact(ContactInterface $contact): static
    {
        $this->crmContacts->removeElement($contact);

        return $this;
    }

    public function getCrmCompany(): ?CrmCompany
    {
        return $this->crmCompany;
    }

    public function setCrmCompany(?CrmCompany $crmCompany): static
    {
        $this->crmCompany = $crmCompany;

        return $this;
    }

    public function getCrmDeal(): ?CrmDeal
    {
        return $this->crmDeal;
    }

    public function setCrmDeal(?CrmDeal $crmDeal): static
    {
        $this->crmDeal = $crmDeal;

        return $this;
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public function addColumn(ProjectColumnInterface $column): static
    {
        if (!$this->columns->contains($column)) {
            $this->columns->add($column);
            $column->setProject($this);
        }

        return $this;
    }
}
