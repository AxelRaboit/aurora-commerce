<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractListingCategoryTranslation implements ListingCategoryTranslationInterface
{
    #[ORM\Column(length: 10)]
    protected string $locale;

    #[ORM\Column(length: 150)]
    protected string $name;

    #[ORM\Column(length: 200)]
    protected string $slug;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(length: 200, nullable: true)]
    protected ?string $seoTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $seoDescription = null;

    #[ORM\ManyToOne(targetEntity: ListingCategoryInterface::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ListingCategoryInterface $category;

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function setSeoTitle(?string $seoTitle): static
    {
        $this->seoTitle = $seoTitle;

        return $this;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    public function setSeoDescription(?string $seoDescription): static
    {
        $this->seoDescription = $seoDescription;

        return $this;
    }

    public function getCategory(): ListingCategoryInterface
    {
        return $this->category;
    }

    public function setCategory(ListingCategoryInterface $category): static
    {
        $this->category = $category;

        return $this;
    }
}
