<?php

declare(strict_types=1);

namespace App\Service\Seo;

use App\Entity\Post;
use App\Entity\PostTranslation;
use App\Entity\Taxonomy;
use App\Entity\TaxonomyTerm;
use App\Entity\TaxonomyTermTranslation;
use App\Service\FrontContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AlternatesBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private FrontContext $frontContext,
    ) {}

    /**
     * @return list<array{locale: string, url: string}>
     */
    public function forPost(Post $post): array
    {
        $alternates = [];
        foreach ($this->frontContext->activeLocaleCodes() as $code) {
            $translation = $post->getTranslation($code);
            if (!$translation instanceof PostTranslation) {
                continue;
            }

            $slug = $translation->getSlug();
            if (null === $slug) {
                continue;
            }

            if ('' === $slug) {
                continue;
            }

            $alternates[] = [
                'locale' => $code,
                'url' => $this->urlGenerator->generate('front_post', [
                    'locale' => $code,
                    'postTypeSlug' => $post->getPostType()->getSlug(),
                    'slug' => $slug,
                ]),
            ];
        }

        return $alternates;
    }

    /**
     * @param array<string, string> $extraParams
     *
     * @return list<array{locale: string, url: string}>
     */
    public function forRoute(string $route, array $extraParams = []): array
    {
        $alternates = [];
        foreach ($this->frontContext->activeLocaleCodes() as $code) {
            $alternates[] = [
                'locale' => $code,
                'url' => $this->urlGenerator->generate($route, array_merge($extraParams, ['locale' => $code])),
            ];
        }

        return $alternates;
    }

    /**
     * @return list<array{locale: string, url: string}>
     */
    public function forTerm(Taxonomy $taxonomy, TaxonomyTerm $term): array
    {
        $alternates = [];
        foreach ($this->frontContext->activeLocaleCodes() as $code) {
            $termTranslation = $term->getTranslation($code);
            if (!$termTranslation instanceof TaxonomyTermTranslation) {
                continue;
            }

            if ('' === $termTranslation->getSlug()) {
                continue;
            }

            $alternates[] = [
                'locale' => $code,
                'url' => $this->urlGenerator->generate('front_term', [
                    'locale' => $code,
                    'taxonomySlug' => $taxonomy->getSlug(),
                    'termSlug' => $termTranslation->getSlug(),
                ]),
            ];
        }

        return $alternates;
    }
}
