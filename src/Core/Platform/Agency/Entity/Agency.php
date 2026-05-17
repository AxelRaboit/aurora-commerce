<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Agency\Entity;

use Aurora\Core\Platform\Agency\Repository\AgencyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgencyRepository::class)]
#[ORM\Table(name: 'core_agencies')]
class Agency extends AbstractAgency
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_agency_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
