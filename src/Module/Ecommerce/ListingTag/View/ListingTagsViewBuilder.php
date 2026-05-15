<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\View;

use Aurora\Core\Locale\Service\LocaleOptionsProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin listing tags index view.
 */
final readonly class ListingTagsViewBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private LocaleOptionsProviderInterface $localeOptionsProvider,
    ) {}

    /**
     * @param list<array<string, mixed>> $tags
     *
     * @return array<string, mixed>
     */
    public function indexView(array $tags): array
    {
        return [
            'tags' => $tags,
            'locales' => $this->localeOptionsProvider->getActiveOptions(),
            'listPath' => $this->urlGenerator->generate('backend_ecommerce_listing_tags_list'),
            'createPath' => $this->urlGenerator->generate('backend_ecommerce_listing_tags_create'),
            'updatePath' => $this->urlGenerator->generate('backend_ecommerce_listing_tags_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_ecommerce_listing_tags_delete', ['id' => '__id__']),
        ];
    }
}
