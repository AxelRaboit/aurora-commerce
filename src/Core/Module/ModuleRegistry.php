<?php

declare(strict_types=1);

namespace Aurora\Core\Module;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final readonly class ModuleRegistry
{
    /** @param iterable<ModuleInterface> $modules */
    public function __construct(
        private iterable $modules,
        private AuthorizationCheckerInterface $security,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * Returns resolved nav sections (permissions filtered, paths generated, sorted by priority).
     *
     * @return array<int, array{id: string, items: array<int, array{route: string, path: string, labelKey: string, icon: string, activeColor: string}>}>
     */
    public function getNavSections(): array
    {
        $sections = [];
        $insertion = 0;

        foreach ($this->modules as $module) {
            foreach ($module->getNavSections() as $section) {
                $resolvedItems = [];

                foreach ($section->items as $item) {
                    $resolved = $this->resolveItem($item);
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

    /** @return array<string, mixed>|null null when the item is filtered by role */
    private function resolveItem(NavItem $item): ?array
    {
        if (null !== $item->requiredPrivilege && !$this->security->isGranted($item->requiredPrivilege)) {
            return null;
        }

        $children = [];
        foreach ($item->children as $child) {
            $resolved = $this->resolveItem($child);
            if (null !== $resolved) {
                $children[] = $resolved;
            }
        }

        return [
            'route' => $item->activeRoutePrefix ?? $item->route,
            'path' => $this->urlGenerator->generate($item->route),
            'labelKey' => $item->labelKey,
            'icon' => $item->icon,
            'activeColor' => $item->activeColor,
            'children' => $children,
        ];
    }
}
