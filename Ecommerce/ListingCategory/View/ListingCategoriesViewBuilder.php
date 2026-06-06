<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\View;

use Aurora\Core\Locale\Service\LocaleOptionsProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin listing categories index view.
 */
final readonly class ListingCategoriesViewBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private LocaleOptionsProviderInterface $localeOptionsProvider,
    ) {}

    /**
     * @param list<array<string, mixed>> $categories
     *
     * @return array<string, mixed>
     */
    public function indexView(array $categories): array
    {
        return [
            'categories' => $categories,
            'locales' => $this->localeOptionsProvider->getActiveOptions(),
            'listPath' => $this->urlGenerator->generate('backend_ecommerce_listing_categories_list'),
            'createPath' => $this->urlGenerator->generate('backend_ecommerce_listing_categories_create'),
            'updatePath' => $this->urlGenerator->generate('backend_ecommerce_listing_categories_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_ecommerce_listing_categories_delete', ['id' => '__id__']),
            'reorderPath' => $this->urlGenerator->generate('backend_ecommerce_listing_categories_reorder'),
        ];
    }
}
