<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\View;

use Aurora\Core\Locale\Repository\LocaleRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin listing categories index view.
 */
final readonly class ListingCategoriesViewBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private LocaleRepository $localeRepository,
    ) {}

    /**
     * @param list<array<string, mixed>> $categories
     *
     * @return array<string, mixed>
     */
    public function indexView(array $categories): array
    {
        $locales = array_map(static fn ($locale): array => [
            'code' => $locale->getCode(),
            'label' => $locale->getName(),
        ], $this->localeRepository->findAll());

        return [
            'categories' => $categories,
            'locales' => $locales,
            'listPath' => $this->urlGenerator->generate('backend_ecommerce_listing_categories_list'),
            'createPath' => $this->urlGenerator->generate('backend_ecommerce_listing_categories_create'),
            'updatePath' => $this->urlGenerator->generate('backend_ecommerce_listing_categories_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_ecommerce_listing_categories_delete', ['id' => '__id__']),
        ];
    }
}
