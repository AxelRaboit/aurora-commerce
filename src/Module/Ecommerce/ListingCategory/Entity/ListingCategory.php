<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Entity;

use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\ListingCategory\Repository\ListingCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ListingCategoryRepository::class)]
#[ORM\Table(name: 'core_ecommerce_listing_categories')]
class ListingCategory extends AbstractListingCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_listing_category_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    /** @var Collection<int, ListingCategoryInterface> */
    #[ORM\OneToMany(targetEntity: ListingCategoryInterface::class, mappedBy: 'parent')]
    protected Collection $children;

    /** @var Collection<string, ListingCategoryTranslationInterface> */
    #[ORM\OneToMany(
        targetEntity: ListingCategoryTranslationInterface::class,
        mappedBy: 'category',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
        indexBy: 'locale',
    )]
    protected Collection $translations;

    /** @var Collection<int, ListingInterface> */
    #[ORM\ManyToMany(targetEntity: ListingInterface::class, mappedBy: 'categories')]
    protected Collection $listings;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->listings = new ArrayCollection();
    }

    public function getListings(): Collection
    {
        return $this->listings;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(ListingCategoryInterface $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(ListingCategoryInterface $child): static
    {
        if ($this->children->removeElement($child) && $child->getParent() === $this) {
            $child->setParent(null);
        }

        return $this;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?ListingCategoryTranslationInterface
    {
        return $this->translations->get($locale);
    }

    public function addTranslation(ListingCategoryTranslationInterface $translation): static
    {
        $locale = $translation->getLocale();
        if (!$this->translations->containsKey($locale)) {
            $this->translations->set($locale, $translation);
            $translation->setCategory($this);
        }

        return $this;
    }

    public function removeTranslation(ListingCategoryTranslationInterface $translation): static
    {
        $this->translations->removeElement($translation);

        return $this;
    }

    public function translate(string $locale): ListingCategoryTranslationInterface
    {
        if ($this->translations->containsKey($locale)) {
            return $this->translations->get($locale);
        }

        $translation = new ListingCategoryTranslation();
        $translation->setLocale($locale);
        $translation->setCategory($this);

        $this->translations->set($locale, $translation);

        return $translation;
    }
}
