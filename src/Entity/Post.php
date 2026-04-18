<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'posts')]
class Post implements TimestampableInterface
{
    use TimestampableTrait;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_TRASH = 'trash';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\ManyToOne(targetEntity: PostType::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private PostType $postType;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Media $featuredMedia = null;

    #[ORM\OneToMany(targetEntity: PostTranslation::class, mappedBy: 'post', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPostType(): PostType
    {
        return $this->postType;
    }

    public function setPostType(PostType $postType): static
    {
        $this->postType = $postType;

        return $this;
    }

    public function getFeaturedMedia(): ?Media
    {
        return $this->featuredMedia;
    }

    public function setFeaturedMedia(?Media $featuredMedia): static
    {
        $this->featuredMedia = $featuredMedia;

        return $this;
    }

    /** @return Collection<string, PostTranslation> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?PostTranslation
    {
        return $this->translations->get($locale);
    }

    public function translate(string $locale): PostTranslation
    {
        if ($this->translations->containsKey($locale)) {
            return $this->translations->get($locale);
        }

        $translation = new PostTranslation();
        $translation->setPost($this);
        $translation->setLocale($locale);

        $this->translations->set($locale, $translation);

        return $translation;
    }

    public function isPublished(): bool
    {
        return self::STATUS_PUBLISHED === $this->status;
    }
}
