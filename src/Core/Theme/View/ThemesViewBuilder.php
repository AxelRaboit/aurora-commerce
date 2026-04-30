<?php

declare(strict_types=1);

namespace Aurora\Core\Theme\View;

use Aurora\Core\Theme\Repository\ThemeRepository;
use Aurora\Core\Theme\Serializer\ThemeSerializer;

/**
 * Builds the Twig payload for the admin themes page. Centralises the theme
 * list serialisation so the controller stays focused on JSON CRUD operations.
 */
final readonly class ThemesViewBuilder
{
    public function __construct(
        private ThemeRepository $themeRepository,
        private ThemeSerializer $themeSerializer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        return [
            'themes' => array_map($this->themeSerializer->serialize(...), $this->themeRepository->findAll()),
        ];
    }
}
