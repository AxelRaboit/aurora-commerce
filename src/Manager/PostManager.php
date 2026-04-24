<?php

declare(strict_types=1);

namespace App\Manager;

use App\Contract\PostManagerInterface;
use App\DTO\PostInput;
use App\DTO\PostTranslationInput;
use App\Entity\Post;
use App\Entity\PostRevision;
use App\Entity\User;
use App\Enum\ApplicationParameter\VeloxApplicationParameterEnum;
use App\Enum\PostStatusEnum;
use App\Repository\MediaRepository;
use App\Repository\PostRevisionRepository;
use App\Repository\PostSlugHistoryRepository;
use App\Repository\PostTypeRepository;
use App\Repository\SettingRepository;
use App\Repository\TagRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\String\Slugger\SluggerInterface;

use const DATE_ATOM;

#[AsAlias(PostManagerInterface::class)]
final readonly class PostManager implements PostManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PostTypeRepository $postTypeRepository,
        private TagRepository $tagRepository,
        private MediaRepository $mediaRepository,
        private PostRevisionRepository $revisionRepository,
        private PostSlugHistoryRepository $slugHistoryRepository,
        private SettingRepository $settingRepository,
        private SluggerInterface $slugger,
        private Security $security,
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

        $this->snapshotRevision($post);
    }

    public function delete(Post $post): void
    {
        if ($post->isTrashed()) {
            return;
        }

        $post->setDeletedAt(new DateTimeImmutable());
        $post->updateTimestamps();

        $this->entityManager->flush();
    }

    public function restore(Post $post): void
    {
        $post->setDeletedAt(null);
        $post->updateTimestamps();

        $this->entityManager->flush();
    }

    public function forceDelete(Post $post): void
    {
        $this->entityManager->remove($post);
        $this->entityManager->flush();
    }

    public function restoreRevision(Post $post, PostRevision $revision): void
    {
        $snapshot = $revision->getSnapshot();

        $post->setStatus(PostStatusEnum::from($snapshot['status'] ?? PostStatusEnum::Draft->value));

        $post->setPublishedAt($this->hydrateDate($snapshot['publishedAt'] ?? null));
        $post->setScheduledAt($this->hydrateDate($snapshot['scheduledAt'] ?? null));

        $featuredMediaId = $snapshot['featuredMediaId'] ?? null;
        $post->setFeaturedMedia(null !== $featuredMediaId ? $this->mediaRepository->find($featuredMediaId) : null);

        $this->syncTags($post, array_values(array_filter(
            array_map(intval(...), $snapshot['tagIds'] ?? []),
            static fn (int $tagId): bool => $tagId > 0,
        )));

        foreach ((array) ($snapshot['translations'] ?? []) as $locale => $translationData) {
            if (!is_array($translationData)) {
                continue;
            }

            $translation = $post->translate((string) $locale);
            $translation->setTitle($translationData['title'] ?? null);
            $translation->setSlug($translationData['slug'] ?? null);
            $translation->setBlocks($translationData['blocks'] ?? []);
            $translation->setMetaTitle($translationData['metaTitle'] ?? null);
            $translation->setMetaDescription($translationData['metaDescription'] ?? null);
            $translation->setCustomFields($translationData['customFields'] ?? []);

            $ogImageId = $translationData['ogImageMediaId'] ?? null;
            $translation->setOgImage(null !== $ogImageId ? $this->mediaRepository->find($ogImageId) : null);
            $translation->setCanonicalUrl($translationData['canonicalUrl'] ?? null);
            $translation->setNoindex((bool) ($translationData['noindex'] ?? false));
            $translation->setFocusKeyword($translationData['focusKeyword'] ?? null);
            $jsonLd = $translationData['jsonLd'] ?? null;
            $translation->setJsonLd(is_array($jsonLd) ? $jsonLd : null);
        }

        $post->updateTimestamps();
        $this->entityManager->flush();

        $this->snapshotRevision($post);
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

        if (PostStatusEnum::Published === $status && !$post->getPublishedAt() instanceof DateTimeImmutable) {
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

        $translation->setOgImage(
            null !== $input->ogImageMediaId ? $this->mediaRepository->find($input->ogImageMediaId) : null,
        );
        $translation->setCanonicalUrl($input->canonicalUrl);
        $translation->setNoindex($input->noindex);
        $translation->setFocusKeyword($input->focusKeyword);
        $translation->setJsonLd($input->jsonLd);

        $previousSlug = $translation->getSlug();
        $newSlug = $input->slug ?: ($input->title ? $this->slugger->slug($input->title)->lower()->toString() : null);

        if ($newSlug !== $previousSlug) {
            if (null !== $newSlug) {
                // If the new slug appears in history, remove that entry to avoid a self-redirect.
                $this->slugHistoryRepository->removeByLocaleAndSlug($locale, $newSlug);
            }
            if (null !== $previousSlug && '' !== $previousSlug) {
                $this->slugHistoryRepository->recordIfNew($post, $locale, $previousSlug);
            }
            $translation->setSlug($newSlug);
        }
    }

    private function snapshotRevision(Post $post): void
    {
        $revision = new PostRevision();
        $revision->setPost($post);
        $revision->setPostVersion($post->getVersion());
        $revision->setStatus($post->getStatus());
        $revision->setSnapshot($this->buildSnapshot($post));

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $revision->setAuthor($user);
        }

        $this->entityManager->persist($revision);
        $this->entityManager->flush();

        $limit = (int) $this->settingRepository->get(
            VeloxApplicationParameterEnum::PostRevisionsLimit->value,
            VeloxApplicationParameterEnum::PostRevisionsLimit->getDefaultValue(),
        );

        if ($limit > 0) {
            $this->revisionRepository->pruneOlderThanLimit($post, $limit);
        }
    }

    /** @return array<string, mixed> */
    private function buildSnapshot(Post $post): array
    {
        $translations = [];
        foreach ($post->getTranslations() as $locale => $translation) {
            $translations[(string) $locale] = [
                'title' => $translation->getTitle(),
                'slug' => $translation->getSlug(),
                'blocks' => $translation->getBlocks(),
                'metaTitle' => $translation->getMetaTitle(),
                'metaDescription' => $translation->getMetaDescription(),
                'customFields' => $translation->getCustomFields(),
                'ogImageMediaId' => $translation->getOgImage()?->getId(),
                'canonicalUrl' => $translation->getCanonicalUrl(),
                'noindex' => $translation->isNoindex(),
                'focusKeyword' => $translation->getFocusKeyword(),
                'jsonLd' => $translation->getJsonLd(),
            ];
        }

        return [
            'status' => $post->getStatus()->value,
            'postTypeId' => $post->getPostType()->getId(),
            'featuredMediaId' => $post->getFeaturedMedia()?->getId(),
            'tagIds' => $post->getTags()->map(fn ($tag): ?int => $tag->getId())->toArray(),
            'publishedAt' => $post->getPublishedAt()?->format(DATE_ATOM),
            'scheduledAt' => $post->getScheduledAt()?->format(DATE_ATOM),
            'translations' => $translations,
        ];
    }

    private function hydrateDate(?string $value): ?DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }
}
