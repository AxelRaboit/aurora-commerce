<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Entity;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Planning\Event\Repository\PlanningEventRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningEventRepository::class)]
#[ORM\Table(name: 'core_planning_events')]
#[ORM\Index(name: 'idx_planning_event_planning_start', columns: ['planning_id', 'start_at'])]
#[ORM\UniqueConstraint(name: 'uniq_planning_event_source', columns: ['source_type', 'source_id'])]
class PlanningEvent extends AbstractPlanningEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_core_planning_event_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    /** @var Collection<int, CoreUserInterface> */
    #[ORM\ManyToMany(targetEntity: CoreUserInterface::class)]
    #[ORM\JoinTable(name: 'core_planning_event_attendees')]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Collection $attendees;

    public function getId(): ?int
    {
        return $this->id;
    }
}
