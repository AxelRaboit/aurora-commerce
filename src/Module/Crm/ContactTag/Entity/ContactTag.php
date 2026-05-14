<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Entity;

use Aurora\Module\Crm\ContactTag\Repository\ContactTagRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactTagRepository::class)]
#[ORM\Table(name: 'core_crm_contact_tags')]
class ContactTag extends AbstractContactTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_contact_tag_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
