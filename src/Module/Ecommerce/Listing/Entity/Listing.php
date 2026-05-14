<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Entity;

use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ListingRepository::class)]
#[ORM\Table(name: 'core_ecommerce_listings')]
#[ORM\UniqueConstraint(name: 'uniq_ecommerce_listing_slug', columns: ['slug'])]
class Listing extends AbstractListing
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_listing_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    /** @var Collection<int, ListingCategoryInterface> */
    #[ORM\ManyToMany(targetEntity: ListingCategoryInterface::class, inversedBy: 'listings')]
    #[ORM\JoinTable(name: 'core_ecommerce_listing_category_map')]
    #[ORM\JoinColumn(name: 'listing_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'listing_category_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Collection $categories;

    /** @var Collection<int, ListingTagInterface> */
    #[ORM\ManyToMany(targetEntity: ListingTagInterface::class, inversedBy: 'listings')]
    #[ORM\JoinTable(name: 'core_ecommerce_listing_tag_map')]
    #[ORM\JoinColumn(name: 'listing_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'listing_tag_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Collection $tags;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(ListingCategoryInterface $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(ListingCategoryInterface $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    public function clearCategories(): static
    {
        $this->categories->clear();

        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(ListingTagInterface $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(ListingTagInterface $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function clearTags(): static
    {
        $this->tags->clear();

        return $this;
    }
}
