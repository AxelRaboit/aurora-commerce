<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\View;

use Aurora\Core\Locale\Repository\LocaleRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin listing tags index view.
 */
final readonly class ListingTagsViewBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private LocaleRepository $localeRepository,
    ) {}

    /**
     * @param list<array<string, mixed>> $tags
     *
     * @return array<string, mixed>
     */
    public function indexView(array $tags): array
    {
        $locales = array_map(static fn ($locale): array => [
            'code' => $locale->getCode(),
            'label' => $locale->getName(),
        ], $this->localeRepository->findAll());

        return [
            'tags' => $tags,
            'locales' => $locales,
            'listPath' => $this->urlGenerator->generate('backend_ecommerce_listing_tags_list'),
            'createPath' => $this->urlGenerator->generate('backend_ecommerce_listing_tags_create'),
            'updatePath' => $this->urlGenerator->generate('backend_ecommerce_listing_tags_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_ecommerce_listing_tags_delete', ['id' => '__id__']),
        ];
    }
}
