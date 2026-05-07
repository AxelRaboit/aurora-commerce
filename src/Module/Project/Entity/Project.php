<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Crm\Company\Entity\Company as CrmCompany;
use Aurora\Module\Crm\Contact\Entity\Contact as CrmContact;
use Aurora\Module\Crm\Deal\Entity\Deal as CrmDeal;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use Aurora\Module\Project\Repository\ProjectRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'core_projects')]
#[ORM\HasLifecycleCallbacks]
class Project
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_project_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, enumType: ProjectStatusEnum::class, options: ['default' => 'draft'])]
    private ProjectStatusEnum $status = ProjectStatusEnum::Draft;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $endDate = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $responsibleUser = null;

    /** @var Collection<int, CrmContact> */
    #[ORM\ManyToMany(targetEntity: CrmContact::class)]
    #[ORM\JoinTable(name: 'core_project_crm_contacts')]
    #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'contact_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $crmContacts;

    #[ORM\ManyToOne(targetEntity: CrmCompany::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CrmCompany $crmCompany = null;

    #[ORM\ManyToOne(targetEntity: CrmDeal::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CrmDeal $crmDeal = null;

    /** @var Collection<int, ProjectTask> */
    #[ORM\OneToMany(targetEntity: ProjectTask::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'createdAt' => 'ASC'])]
    private Collection $tasks;

    /** @var Collection<int, ProjectColumn> */
    #[ORM\OneToMany(targetEntity: ProjectColumn::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $columns;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->crmContacts = new ArrayCollection();
        $this->columns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getResponsibleUser(): ?User
    {
        return $this->responsibleUser;
    }

    public function setResponsibleUser(?User $responsibleUser): static
    {
        $this->responsibleUser = $responsibleUser;

        return $this;
    }

    /** @return Collection<int, CrmContact> */
    public function getCrmContacts(): Collection
    {
        return $this->crmContacts;
    }

    public function addCrmContact(CrmContact $contact): static
    {
        if (!$this->crmContacts->contains($contact)) {
            $this->crmContacts->add($contact);
        }

        return $this;
    }

    public function removeCrmContact(CrmContact $contact): static
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

    /** @return Collection<int, ProjectTask> */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    /** @return Collection<int, ProjectColumn> */
    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public function addColumn(ProjectColumn $column): static
    {
        if (!$this->columns->contains($column)) {
            $this->columns->add($column);
            $column->setProject($this);
        }

        return $this;
    }
}
