<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Entity;

use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\ContactTag\Repository\ContactTagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /** @var Collection<int, ContactInterface> */
    #[ORM\ManyToMany(targetEntity: ContactInterface::class, mappedBy: 'contactTags')]
    protected Collection $contacts;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContacts(): Collection
    {
        return $this->contacts;
    }
}
