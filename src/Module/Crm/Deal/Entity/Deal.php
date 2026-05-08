<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Entity;

use Aurora\Module\Crm\Deal\Repository\DealRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DealRepository::class)]
#[ORM\Table(name: 'core_crm_deals')]
class Deal extends AbstractDeal
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_deal_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;
}
