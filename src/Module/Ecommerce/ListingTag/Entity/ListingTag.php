<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Entity;

use Aurora\Module\Ecommerce\ListingTag\Repository\ListingTagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ListingTagRepository::class)]
#[ORM\Table(name: 'core_ecommerce_listing_tags')]
class ListingTag extends AbstractListingTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_listing_tag_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    /** @var Collection<string, ListingTagTranslationInterface> */
    #[ORM\OneToMany(
        targetEntity: ListingTagTranslationInterface::class,
        mappedBy: 'tag',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
        indexBy: 'locale',
    )]
    protected Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?ListingTagTranslationInterface
    {
        return $this->translations->get($locale);
    }

    public function addTranslation(ListingTagTranslationInterface $translation): static
    {
        $locale = $translation->getLocale();
        if (!$this->translations->containsKey($locale)) {
            $this->translations->set($locale, $translation);
            $translation->setTag($this);
        }

        return $this;
    }

    public function removeTranslation(ListingTagTranslationInterface $translation): static
    {
        $this->translations->removeElement($translation);

        return $this;
    }
}
