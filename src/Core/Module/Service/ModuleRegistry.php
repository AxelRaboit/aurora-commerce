<?php

declare(strict_types=1);

namespace Aurora\Core\Module\Service;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\User\Entity\CoreUserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final readonly class ModuleRegistry
{
    /** @param iterable<ModuleInterface> $modules */
    public function __construct(
        private iterable $modules,
        private AuthorizationCheckerInterface $security,
        private UrlGeneratorInterface $urlGenerator,
        private Security $userSecurity,
    ) {}

    /**
     * Returns resolved nav sections (permissions filtered, paths generated, sorted by priority).
     *
     * Sections and items hidden by the current user (via their personal sidemenu
     * preferences) are excluded from the returned structure but remain reachable
     * by direct URL — the hide is a display preference, not an access control.
     *
     * @return array<int, array{id: string, items: array<int, array{route: string, path: string, labelKey: string, icon: string, activeColor: string}>}>
     */
    public function getNavSections(): array
    {
        $user = $this->userSecurity->getUser();
        $hiddenSections = $user instanceof CoreUserInterface ? $user->getHiddenNavSections() : [];
        $hiddenItems = $user instanceof CoreUserInterface ? $user->getHiddenNavItems() : [];

        $sections = [];
        $insertion = 0;

        foreach ($this->modules as $module) {
            foreach ($module->getNavSections() as $section) {
                if (in_array($section->id, $hiddenSections, true)) {
                    continue;
                }

                $resolvedItems = [];

                foreach ($section->items as $item) {
                    $resolved = $this->resolveItem($item, $hiddenItems);
                    if (null !== $resolved) {
                        $resolvedItems[] = $resolved;
                    }
                }

                if ([] !== $resolvedItems) {
                    $sections[] = [
                        'id' => $section->id,
                        'items' => $resolvedItems,
                        'priority' => $section->priority,
                        'insertion' => $insertion++,
                    ];
                }
            }
        }

        usort($sections, static fn (array $a, array $b): int => [$a['priority'], $a['insertion']] <=> [$b['priority'], $b['insertion']]);

        return array_map(static fn (array $section): array => [
            'id' => $section['id'],
            'items' => $section['items'],
        ], $sections);
    }

    /**
     * Returns the full nav structure available to the current user (filtered only by
     * privilege), annotated with the user's current hidden flags. Used by the
     * sidemenu preferences UI so users can toggle visibility for every available
     * section/item — including ones they have already hidden.
     *
     * @return array<int, array{id: string, hidden: bool, items: array<int, array<string, mixed>>}>
     */
    public function getNavPreferences(): array
    {
        $user = $this->userSecurity->getUser();
        $hiddenSections = $user instanceof CoreUserInterface ? $user->getHiddenNavSections() : [];
        $hiddenItems = $user instanceof CoreUserInterface ? $user->getHiddenNavItems() : [];

        $sections = [];
        $insertion = 0;

        foreach ($this->modules as $module) {
            foreach ($module->getNavSections() as $section) {
                $resolvedItems = [];

                foreach ($section->items as $item) {
                    $resolved = $this->resolveItem($item);
                    if (null !== $resolved) {
                        $resolvedItems[] = $this->annotateHiddenFlags($resolved, $hiddenItems);
                    }
                }

                if ([] !== $resolvedItems) {
                    $sections[] = [
                        'id' => $section->id,
                        'hidden' => in_array($section->id, $hiddenSections, true),
                        'items' => $resolvedItems,
                        'priority' => $section->priority,
                        'insertion' => $insertion++,
                    ];
                }
            }
        }

        usort($sections, static fn (array $a, array $b): int => [$a['priority'], $a['insertion']] <=> [$b['priority'], $b['insertion']]);

        return array_map(static fn (array $section): array => [
            'id' => $section['id'],
            'hidden' => $section['hidden'],
            'items' => $section['items'],
        ], $sections);
    }

    /**
     * @param array<string, mixed> $resolved
     * @param list<string>         $hiddenItems
     *
     * @return array<string, mixed>
     */
    private function annotateHiddenFlags(array $resolved, array $hiddenItems): array
    {
        $resolved['hidden'] = in_array($resolved['key'], $hiddenItems, true);
        $resolved['children'] = array_map(
            fn (array $child): array => $this->annotateHiddenFlags($child, $hiddenItems),
            $resolved['children'],
        );

        return $resolved;
    }

    /**
     * @param list<string> $hiddenItems user-hidden NavItem route names
     *
     * @return array<string, mixed>|null null when the item is filtered by role or hidden by the user
     */
    private function resolveItem(NavItem $item, array $hiddenItems = []): ?array
    {
        if (null !== $item->requiredPrivilege && !$this->security->isGranted($item->requiredPrivilege)) {
            return null;
        }

        if (in_array($item->route, $hiddenItems, true)) {
            return null;
        }

        $children = [];
        foreach ($item->children as $child) {
            $resolved = $this->resolveItem($child, $hiddenItems);
            if (null !== $resolved) {
                $children[] = $resolved;
            }
        }

        return [
            'key' => $item->route,
            'route' => $item->activeRoutePrefix ?? $item->route,
            'path' => $this->urlGenerator->generate($item->route),
            'labelKey' => $item->labelKey,
            'descriptionKey' => $item->descriptionKey,
            'icon' => $item->icon,
            'activeColor' => $item->activeColor,
            'children' => $children,
        ];
    }
}
