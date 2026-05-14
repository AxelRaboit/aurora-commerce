<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\View;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin contact tags index view.
 */
final readonly class ContactTagsViewBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
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
            'listPath' => $this->urlGenerator->generate('backend_crm_contact_tags_list'),
            'createPath' => $this->urlGenerator->generate('backend_crm_contact_tags_create'),
            'updatePath' => $this->urlGenerator->generate('backend_crm_contact_tags_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_crm_contact_tags_delete', ['id' => '__id__']),
        ];
    }
}
