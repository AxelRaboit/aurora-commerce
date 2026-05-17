<?php

declare(strict_types=1);

namespace Aurora\Core\Configuration\Theme\View;

use Aurora\Core\Configuration\Theme\Repository\ThemeRepository;
use Aurora\Core\Configuration\Theme\Serializer\ThemeSerializerInterface;

/**
 * Builds the Twig payload for the admin themes page. Centralises the theme
 * list serialisation so the controller stays focused on JSON CRUD operations.
 */
final readonly class ThemesViewBuilder
{
    public function __construct(
        private ThemeRepository $themeRepository,
        private ThemeSerializerInterface $themeSerializer,
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
