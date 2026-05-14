<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Entity;

use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\Table(name: 'core_crm_contacts')]
class Contact extends AbstractContact
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_contact_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    /** @var Collection<int, ContactTagInterface> */
    #[ORM\ManyToMany(targetEntity: ContactTagInterface::class, inversedBy: 'contacts')]
    #[ORM\JoinTable(name: 'core_crm_contact_tag_map')]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'contact_tag_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Collection $contactTags;

    public function __construct()
    {
        $this->contactTags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContactTags(): Collection
    {
        return $this->contactTags;
    }

    public function addContactTag(ContactTagInterface $contactTag): static
    {
        if (!$this->contactTags->contains($contactTag)) {
            $this->contactTags->add($contactTag);
        }

        return $this;
    }

    public function removeContactTag(ContactTagInterface $contactTag): static
    {
        $this->contactTags->removeElement($contactTag);

        return $this;
    }

    public function clearContactTags(): static
    {
        $this->contactTags->clear();

        return $this;
    }
}
