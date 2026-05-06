<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Serializer;

use Aurora\Core\Menu\Entity\MenuItem;
use Aurora\Core\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class MenuItemSerializer
{
    public function __construct(
        private PostRepository $postRepository,
        private TaxonomyTermRepository $termRepository,
        private PostTypeRepository $postTypeRepository,
        private TranslatorInterface $translator,
    ) {}

    /**
     * @param array<int, Post>         $postCache     keyed by id
     * @param array<int, TaxonomyTerm> $termCache     keyed by id
     * @param array<int, PostType>     $postTypeCache keyed by id
     *
     * @return array<string, mixed>
     */
    public function serialize(MenuItem $item, array $postCache = [], array $termCache = [], array $postTypeCache = []): array
    {
        $translations = [];
        foreach ($item->getTranslations() as $translation) {
            $translations[$translation->getLocale()] = $translation->getLabel();
        }

        $children = [];
        foreach ($item->getChildren() as $child) {
            $children[] = $this->serialize($child, $postCache, $termCache, $postTypeCache);
        }

        return [
            'id' => $item->getId(),
            'targetType' => $item->getTargetType()->value,
            'targetId' => $item->getTargetId(),
            'customUrl' => $item->getCustomUrl(),
            'openInNewTab' => $item->isOpenInNewTab(),
            'cssClass' => $item->getCssClass(),
            'visibility' => $item->getVisibility()->value,
            'position' => $item->getPosition(),
            'parentId' => $item->getParent()?->getId(),
            'translations' => $translations,
            'targetPreview' => $this->resolveTargetPreview($item, $postCache, $termCache, $postTypeCache),
            'children' => $children,
        ];
    }

    /**
     * Pre-loads referenced Posts/Terms/PostTypes in batches so the recursive
     * serialization stays free of N+1 queries.
     *
     * @param iterable<MenuItem> $items
     *
     * @return array{posts: array<int, Post>, terms: array<int, TaxonomyTerm>, postTypes: array<int, PostType>}
     */
    public function preloadTargets(iterable $items): array
    {
        $postIds = [];
        $termIds = [];
        $postTypeIds = [];

        $this->collectTargetIds($items, $postIds, $termIds, $postTypeIds);

        $posts = [];
        foreach ($this->postRepository->findByIds(array_values(array_unique($postIds))) as $post) {
            $posts[$post->getId()] = $post;
        }

        $terms = [];
        foreach ($this->termRepository->findByIds(array_values(array_unique($termIds))) as $term) {
            $terms[$term->getId()] = $term;
        }

        $postTypes = [];
        foreach ($this->postTypeRepository->findByIds(array_values(array_unique($postTypeIds))) as $postType) {
            $postTypes[$postType->getId()] = $postType;
        }

        return ['posts' => $posts, 'terms' => $terms, 'postTypes' => $postTypes];
    }

    /**
     * @param iterable<MenuItem> $items
     * @param list<int>          $postIds
     * @param list<int>          $termIds
     * @param list<int>          $postTypeIds
     */
    private function collectTargetIds(iterable $items, array &$postIds, array &$termIds, array &$postTypeIds): void
    {
        foreach ($items as $item) {
            $targetId = $item->getTargetId();
            if (null !== $targetId) {
                match ($item->getTargetType()) {
                    MenuItemTargetTypeEnum::Post => $postIds[] = $targetId,
                    MenuItemTargetTypeEnum::Term => $termIds[] = $targetId,
                    MenuItemTargetTypeEnum::PostTypeArchive => $postTypeIds[] = $targetId,
                    default => null,
                };
            }

            $this->collectTargetIds($item->getChildren(), $postIds, $termIds, $postTypeIds);
        }
    }

    /**
     * @param array<int, Post>         $postCache
     * @param array<int, TaxonomyTerm> $termCache
     * @param array<int, PostType>     $postTypeCache
     *
     * @return array<string, mixed>
     */
    private function resolveTargetPreview(MenuItem $item, array $postCache, array $termCache, array $postTypeCache): array
    {
        return match ($item->getTargetType()) {
            MenuItemTargetTypeEnum::Home => ['label' => $this->translator->trans('frontend.menu.home'), 'hint' => '/'],
            MenuItemTargetTypeEnum::FrontLogin => ['label' => $this->translator->trans('frontend.menu.login'), 'hint' => '/login'],
            MenuItemTargetTypeEnum::FrontRegister => ['label' => $this->translator->trans('frontend.menu.register'), 'hint' => '/register'],
            MenuItemTargetTypeEnum::FrontAccount => ['label' => $this->translator->trans('frontend.menu.account'), 'hint' => '/account'],
            MenuItemTargetTypeEnum::FrontLogout => ['label' => $this->translator->trans('frontend.menu.logout'), 'hint' => '/logout'],
            MenuItemTargetTypeEnum::FrontShop => ['label' => $this->translator->trans('frontend.shop.title'), 'hint' => '/shop'],
            MenuItemTargetTypeEnum::CustomUrl => ['label' => $item->getCustomUrl() ?? '', 'hint' => $item->getCustomUrl() ?? ''],
            MenuItemTargetTypeEnum::Post => $this->postPreview($item, $postCache),
            MenuItemTargetTypeEnum::Term => $this->termPreview($item, $termCache),
            MenuItemTargetTypeEnum::PostTypeArchive => $this->archivePreview($item, $postTypeCache),
        };
    }

    /**
     * @param array<int, Post> $postCache
     *
     * @return array<string, mixed>
     */
    private function postPreview(MenuItem $item, array $postCache): array
    {
        $targetId = $item->getTargetId();
        $post = null !== $targetId ? ($postCache[$targetId] ?? $this->postRepository->find($targetId)) : null;
        if (null === $post) {
            return [
                'label' => $this->translator->trans('backend.menus.preview.post_deleted'),
                'hint' => '#'.$item->getTargetId(),
                'missing' => true,
            ];
        }

        $translation = $post->getTranslations()->first() ?: null;

        return [
            'label' => $translation?->getTitle() ?? $this->translator->trans('backend.menus.preview.untitled'),
            'hint' => sprintf('/%s/%s', $post->getPostType()->getSlug(), $translation?->getSlug() ?? ''),
        ];
    }

    /**
     * @param array<int, TaxonomyTerm> $termCache
     *
     * @return array<string, mixed>
     */
    private function termPreview(MenuItem $item, array $termCache): array
    {
        $targetId = $item->getTargetId();
        $term = null !== $targetId ? ($termCache[$targetId] ?? $this->termRepository->find($targetId)) : null;
        if (null === $term) {
            return [
                'label' => $this->translator->trans('backend.menus.preview.term_deleted'),
                'hint' => '#'.$item->getTargetId(),
                'missing' => true,
            ];
        }

        $translation = $term->getTranslations()->first() ?: null;

        return [
            'label' => $translation?->getName() ?? $this->translator->trans('backend.menus.preview.unnamed'),
            'hint' => sprintf('/%s/%s', $term->getTaxonomy()->getSlug(), $translation?->getSlug() ?? ''),
        ];
    }

    /**
     * @param array<int, PostType> $postTypeCache
     *
     * @return array<string, mixed>
     */
    private function archivePreview(MenuItem $item, array $postTypeCache): array
    {
        $targetId = $item->getTargetId();
        $postType = null !== $targetId ? ($postTypeCache[$targetId] ?? $this->postTypeRepository->find($targetId)) : null;
        if (null === $postType) {
            return [
                'label' => $this->translator->trans('backend.menus.preview.post_type_deleted'),
                'hint' => '#'.$item->getTargetId(),
                'missing' => true,
            ];
        }

        return ['label' => $postType->getLabel(), 'hint' => '/'.$postType->getSlug()];
    }
}
