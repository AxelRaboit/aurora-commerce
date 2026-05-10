<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Entity;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\Erp\Product\Entity\ProductInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractListing implements ListingInterface
{
    use TimestampableTrait;

    #[ORM\OneToOne(targetEntity: ProductInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ProductInterface $product;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(length: 200)]
    protected string $slug;

    #[ORM\Column(length: 200, nullable: true)]
    protected ?string $marketingTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $marketingDescription = null;

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?MediaInterface $featuredImage = null;

    #[ORM\Column(options: ['default' => true])]
    protected bool $isVisibleOnShop = true;

    #[ORM\Column(length: 200, nullable: true)]
    protected ?string $seoTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $seoDescription = null;

    public function getProduct(): ProductInterface
    {
        return $this->product;
    }

    public function setProduct(ProductInterface $product): static
    {
        $this->product = $product;

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

    public function getMarketingTitle(): ?string
    {
        return $this->marketingTitle;
    }

    public function setMarketingTitle(?string $marketingTitle): static
    {
        $this->marketingTitle = $marketingTitle;

        return $this;
    }

    public function getDisplayTitle(): string
    {
        return $this->marketingTitle ?? $this->product->getName();
    }

    public function getMarketingDescription(): ?string
    {
        return $this->marketingDescription;
    }

    public function setMarketingDescription(?string $marketingDescription): static
    {
        $this->marketingDescription = $marketingDescription;

        return $this;
    }

    public function getFeaturedImage(): ?MediaInterface
    {
        return $this->featuredImage;
    }

    public function setFeaturedImage(?MediaInterface $featuredImage): static
    {
        $this->featuredImage = $featuredImage;

        return $this;
    }

    public function isVisibleOnShop(): bool
    {
        return $this->isVisibleOnShop;
    }

    public function setVisibleOnShop(bool $visible): static
    {
        $this->isVisibleOnShop = $visible;

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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }
}
