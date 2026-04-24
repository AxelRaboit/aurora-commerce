<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\PostStatusEnum;
use App\Repository\PostRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'posts')]
class Post implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Version]
    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $version = 1;

    #[ORM\Column(length: 50, enumType: PostStatusEnum::class)]
    private PostStatusEnum $status = PostStatusEnum::Draft;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $scheduledAt = null;

    #[ORM\ManyToOne(targetEntity: PostType::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private PostType $postType;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Media $featuredMedia = null;

    #[ORM\OneToMany(targetEntity: PostTranslation::class, mappedBy: 'post', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    private Collection $translations;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, mappedBy: 'posts')]
    private Collection $tags;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getStatus(): PostStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PostStatusEnum $status): static
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
        return PostStatusEnum::Published === $this->status;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getScheduledAt(): ?DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addPost($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removePost($this);
        }

        return $this;
    }
}
