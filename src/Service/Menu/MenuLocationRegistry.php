<?php

declare(strict_types=1);

namespace App\Service\Menu;

use App\Enum\Menu\MenuItemTargetTypeEnum;
use App\Enum\Menu\MenuItemVisibilityEnum;

/**
 * Registry of menu locations expected by the application/theme.
 *
 * Each location has a slug (used as Menu.location), a default name, an
 * optional description, and an optional list of `defaultItems` seeded the
 * first time the menu is created via `velox:menus:sync`.
 */
final class MenuLocationRegistry
{
    /**
     * @var array<string, array{
     *     name: string,
     *     description: ?string,
     *     defaultItems: array<int, array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum}>,
     * }>
     */
    private array $locations = [
        'primary' => [
            'name' => 'Menu principal',
            'description' => 'Navigation affichée dans le header du site public.',
            'defaultItems' => [],
        ],
        'footer' => [
            'name' => 'Menu pied de page',
            'description' => 'Liens secondaires affichés dans le footer.',
            'defaultItems' => [],
        ],
        'account' => [
            'name' => 'Menu compte',
            'description' => 'Dropdown utilisateur dans le header (connexion, profil, déconnexion).',
            'defaultItems' => [
                ['targetType' => MenuItemTargetTypeEnum::FrontAccount, 'visibility' => MenuItemVisibilityEnum::AuthenticatedOnly],
                ['targetType' => MenuItemTargetTypeEnum::FrontLogin, 'visibility' => MenuItemVisibilityEnum::GuestsOnly],
                ['targetType' => MenuItemTargetTypeEnum::FrontRegister, 'visibility' => MenuItemVisibilityEnum::GuestsOnly],
                ['targetType' => MenuItemTargetTypeEnum::FrontLogout, 'visibility' => MenuItemVisibilityEnum::AuthenticatedOnly],
            ],
        ],
    ];

    /**
     * @return array<string, array{name: string, description: ?string, defaultItems: array<int, array<string, mixed>>}>
     */
    public function all(): array
    {
        return $this->locations;
    }

    /**
     * @param array<int, array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum}> $defaultItems
     */
    public function register(string $location, string $name, ?string $description = null, array $defaultItems = []): void
    {
        $this->locations[$location] = [
            'name' => $name,
            'description' => $description,
            'defaultItems' => $defaultItems,
        ];
    }

    public function has(string $location): bool
    {
        return isset($this->locations[$location]);
    }
}
