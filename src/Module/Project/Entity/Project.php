<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Module\Crm\Contact\Entity\ContactInterface as CrmContact;
use Aurora\Module\Project\Repository\ProjectRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'core_projects')]
class Project extends AbstractProject
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_core_project_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    /** @var Collection<int, CrmContact> */
    #[ORM\ManyToMany(targetEntity: CrmContact::class)]
    #[ORM\JoinTable(name: 'core_project_crm_contacts')]
    #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'contact_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Collection $crmContacts;

    public function getId(): ?int
    {
        return $this->id;
    }
}
