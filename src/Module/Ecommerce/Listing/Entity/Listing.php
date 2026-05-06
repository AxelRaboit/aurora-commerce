<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Entity;

use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Erp\Product\Entity\Product;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ListingRepository::class)]
#[ORM\Table(name: 'ecommerce_listings')]
#[ORM\UniqueConstraint(name: 'uniq_ecommerce_listing_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class Listing
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_listing_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 200)]
    private string $slug;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $marketingTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $marketingDescription = null;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Media $featuredImage = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isVisibleOnShop = true;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $seoTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $seoDescription = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
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

    public function getFeaturedImage(): ?Media
    {
        return $this->featuredImage;
    }

    public function setFeaturedImage(?Media $featuredImage): static
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
