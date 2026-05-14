<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractListingTagTranslation implements ListingTagTranslationInterface
{
    #[ORM\Column(length: 10)]
    protected string $locale;

    #[ORM\Column(length: 100)]
    protected string $name;

    #[ORM\Column(length: 150)]
    protected string $slug;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\ManyToOne(targetEntity: ListingTagInterface::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ListingTagInterface $tag;

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

    public function getTag(): ListingTagInterface
    {
        return $this->tag;
    }

    public function setTag(ListingTagInterface $tag): static
    {
        $this->tag = $tag;

        return $this;
    }
}
