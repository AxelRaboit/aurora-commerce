<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\View;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PostItNotesViewBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(): array
    {
        return [
            'listPath' => $this->urlGenerator->generate('backend_notes_post_it_list'),
            'createPath' => $this->urlGenerator->generate('backend_notes_post_it_create'),
            'updatePath' => $this->urlGenerator->generate('backend_notes_post_it_update', ['id' => '__id__']),
            'movePath' => $this->urlGenerator->generate('backend_notes_post_it_move', ['id' => '__id__']),
            'resizePath' => $this->urlGenerator->generate('backend_notes_post_it_resize', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_notes_post_it_delete', ['id' => '__id__']),
        ];
    }
}
