<?php

declare(strict_types=1);

namespace App\Manager;

use App\Contract\PostManagerInterface;
use App\DTO\PostInput;
use App\DTO\PostTranslationInput;
use App\Entity\Post;
use App\Enum\PostStatusEnum;
use App\Repository\MediaRepository;
use App\Repository\PostTypeRepository;
use App\Repository\TagRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsAlias(PostManagerInterface::class)]
final readonly class PostManager implements PostManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PostTypeRepository $postTypeRepository,
        private TagRepository $tagRepository,
        private MediaRepository $mediaRepository,
        private SluggerInterface $slugger,
    ) {}

    public function create(PostInput $input): Post
    {
        $post = new Post();
        $this->applyInput($post, $input);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    public function update(Post $post, PostInput $input): void
    {
        $this->applyInput($post, $input);
        // Force the Post entity to be marked as dirty so Doctrine's @Version increments
        // even when only related entities (translations, tags) changed — @Version only
        // bumps when the owning entity itself is scheduled for UPDATE.
        $post->updateTimestamps();
        $this->entityManager->flush();
    }

    public function delete(Post $post): void
    {
        $this->entityManager->remove($post);
        $this->entityManager->flush();
    }

    private function applyInput(Post $post, PostInput $input): void
    {
        $postType = $this->postTypeRepository->find($input->postTypeId);
        if (null === $postType) {
            throw new InvalidArgumentException(sprintf('PostType with id %d not found.', $input->postTypeId));
        }

        $post->setPostType($postType);

        $status = PostStatusEnum::from($input->status);
        $post->setStatus($status);

        if (PostStatusEnum::Scheduled === $status && null !== $input->scheduledAt) {
            $post->setScheduledAt(new DateTimeImmutable($input->scheduledAt));
        } else {
            $post->setScheduledAt(null);
        }

        if (PostStatusEnum::Published === $status && null === $post->getPublishedAt()) {
            $post->setPublishedAt(new DateTimeImmutable());
        }

        $featuredMedia = null !== $input->featuredMediaId
            ? $this->mediaRepository->find($input->featuredMediaId)
            : null;
        $post->setFeaturedMedia($featuredMedia);

        $this->syncTags($post, $input->tagIds);

        foreach ($input->translations as $locale => $translationInput) {
            $this->applyTranslation($post, $locale, $translationInput);
        }
    }

    /** @param array<int> $tagIds */
    private function syncTags(Post $post, array $tagIds): void
    {
        foreach ($post->getTags() as $existingTag) {
            if (!in_array($existingTag->getId(), $tagIds, true)) {
                $post->removeTag($existingTag);
            }
        }

        $currentTagIds = $post->getTags()->map(fn ($tag): ?int => $tag->getId())->toArray();

        foreach ($tagIds as $tagId) {
            if (!in_array($tagId, $currentTagIds, true)) {
                $tag = $this->tagRepository->find($tagId);
                if (null !== $tag) {
                    $post->addTag($tag);
                }
            }
        }
    }

    private function applyTranslation(Post $post, string $locale, PostTranslationInput $input): void
    {
        $translation = $post->translate($locale);

        $translation->setTitle($input->title);
        $translation->setBlocks($input->blocks);
        $translation->setMetaTitle($input->metaTitle);
        $translation->setMetaDescription($input->metaDescription);
        $translation->setCustomFields($input->customFields);

        $slug = $input->slug ?: ($input->title ? $this->slugger->slug($input->title)->lower()->toString() : null);
        $translation->setSlug($slug);
    }
}
