<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Manager;

use Aurora\Core\Menu\Entity\Menu;
use Aurora\Core\Menu\Entity\MenuItem;
use Aurora\Core\Menu\Entity\MenuItemTranslation;
use Aurora\Core\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Core\Menu\Enum\MenuItemVisibilityEnum;
use Aurora\Core\Menu\Repository\MenuItemRepository;
use Aurora\Core\Menu\Repository\MenuRepository;
use Aurora\Core\Menu\Service\MenuLocationRegistry;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Throwable;

final readonly class MenuManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MenuRepository $menuRepository,
        private MenuItemRepository $menuItemRepository,
        private MenuLocationRegistry $locationRegistry,
    ) {}

    public function isProtected(Menu $menu): bool
    {
        return $this->locationRegistry->has($menu->getLocation());
    }

    // ── Menu ──────────────────────────────────────────────────────────────────

    public function createMenu(string $name, string $location, ?string $description = null): Menu
    {
        if ('' === mb_trim($name)) {
            throw new InvalidArgumentException('admin.menus.errors.name_required');
        }

        if (!preg_match('/^[a-z0-9_-]+$/', $location)) {
            throw new InvalidArgumentException('admin.menus.errors.location_format');
        }

        if ($this->menuRepository->findByLocation($location) instanceof Menu) {
            throw new InvalidArgumentException('admin.menus.errors.location_taken');
        }

        $menu = new Menu();
        $menu->setName($name);
        $menu->setLocation($location);
        $menu->setDescription($description);

        $this->entityManager->persist($menu);
        $this->entityManager->flush();

        return $menu;
    }

    public function updateMenu(Menu $menu, string $name, string $location, ?string $description = null): void
    {
        if ('' === mb_trim($name)) {
            throw new InvalidArgumentException('admin.menus.errors.name_required');
        }

        if (!preg_match('/^[a-z0-9_-]+$/', $location)) {
            throw new InvalidArgumentException('admin.menus.errors.location_format');
        }

        if ($this->isProtected($menu) && $location !== $menu->getLocation()) {
            throw new InvalidArgumentException('admin.menus.errors.location_locked');
        }

        if ($location !== $menu->getLocation()) {
            $existing = $this->menuRepository->findByLocation($location);
            if ($existing instanceof Menu && $existing->getId() !== $menu->getId()) {
                throw new InvalidArgumentException('admin.menus.errors.location_taken');
            }
        }

        $menu->setName($name);
        $menu->setLocation($location);
        $menu->setDescription($description);

        $this->entityManager->flush();
    }

    public function deleteMenu(Menu $menu): void
    {
        if ($this->isProtected($menu)) {
            throw new InvalidArgumentException('admin.menus.errors.menu_protected');
        }

        $this->entityManager->remove($menu);
        $this->entityManager->flush();
    }

    // ── Item ──────────────────────────────────────────────────────────────────

    /**
     * @param array{
     *     customUrl?: ?string,
     *     parentId?: ?int,
     *     openInNewTab?: bool,
     *     cssClass?: ?string,
     *     visibility?: MenuItemVisibilityEnum,
     * } $options
     */
    public function createItem(
        Menu $menu,
        MenuItemTargetTypeEnum $targetType,
        ?int $targetId = null,
        array $options = [],
    ): MenuItem {
        $this->validateTarget($targetType, $targetId, $options['customUrl'] ?? null);

        $parent = null;
        if (!empty($options['parentId'])) {
            $parent = $this->menuItemRepository->find($options['parentId']);
            if (!$parent instanceof MenuItem || $parent->getMenu()->getId() !== $menu->getId()) {
                throw new InvalidArgumentException('admin.menus.errors.parent_invalid');
            }
        }

        $position = $this->nextPosition($menu, $parent);

        $item = new MenuItem();
        $item->setTargetType($targetType);
        $item->setTargetId($targetType->requiresTargetId() ? $targetId : null);
        $item->setCustomUrl($targetType->requiresCustomUrl() ? ($options['customUrl'] ?? null) : null);
        $item->setOpenInNewTab($options['openInNewTab'] ?? false);
        $item->setCssClass($options['cssClass'] ?? null);
        $item->setVisibility($options['visibility'] ?? MenuItemVisibilityEnum::Always);
        $item->setParent($parent);
        $item->setPosition($position);

        $menu->addItem($item);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }

    /**
     * @param array{
     *     customUrl?: ?string,
     *     openInNewTab?: bool,
     *     cssClass?: ?string,
     *     visibility?: MenuItemVisibilityEnum,
     * } $options
     */
    public function updateItem(
        MenuItem $item,
        MenuItemTargetTypeEnum $targetType,
        ?int $targetId = null,
        array $options = [],
    ): void {
        $this->validateTarget($targetType, $targetId, $options['customUrl'] ?? null);

        $item->setTargetType($targetType);
        $item->setTargetId($targetType->requiresTargetId() ? $targetId : null);
        $item->setCustomUrl($targetType->requiresCustomUrl() ? ($options['customUrl'] ?? null) : null);
        $item->setOpenInNewTab($options['openInNewTab'] ?? false);
        $item->setCssClass($options['cssClass'] ?? null);
        $item->setVisibility($options['visibility'] ?? MenuItemVisibilityEnum::Always);

        $this->entityManager->flush();
    }

    public function deleteItem(MenuItem $item): void
    {
        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    /**
     * Atomically reorder items in a menu. The payload is a flat list:
     *   [{id: 12, parentId: null, position: 0}, {id: 13, parentId: 12, position: 0}, ...]
     *
     * Items not in the payload are left untouched.
     *
     * @param array<array{id: int, parentId: ?int, position: int}> $payload
     */
    public function reorderItems(Menu $menu, array $payload): void
    {
        $this->entityManager->beginTransaction();
        try {
            $itemsById = [];
            foreach ($menu->getItems() as $item) {
                $itemsById[$item->getId()] = $item;
            }

            foreach ($payload as $entry) {
                $id = $entry['id'];
                if (!isset($itemsById[$id])) {
                    continue;
                }

                $item = $itemsById[$id];

                $newParent = null;
                if (!empty($entry['parentId'])) {
                    if (!isset($itemsById[$entry['parentId']])) {
                        throw new InvalidArgumentException('admin.menus.errors.parent_invalid');
                    }

                    $newParent = $itemsById[$entry['parentId']];
                    if ($this->wouldCreateCycle($item, $newParent)) {
                        throw new InvalidArgumentException('admin.menus.errors.parent_cycle');
                    }
                }

                $item->setParent($newParent);
                $item->setPosition((int) $entry['position']);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $throwable) {
            $this->entityManager->rollback();
            throw $throwable;
        }
    }

    // ── Translations ──────────────────────────────────────────────────────────

    /**
     * Set (or create) a translation override for an item.
     * Pass null/empty to remove the override (label will fall back to target's own label).
     */
    public function setTranslation(MenuItem $item, string $locale, ?string $label): void
    {
        $existing = $item->getTranslation($locale);
        $clean = null === $label ? null : mb_trim($label);

        if (null === $clean || '' === $clean) {
            if ($existing instanceof MenuItemTranslation) {
                $item->removeTranslation($existing);
                $this->entityManager->remove($existing);
                $this->entityManager->flush();
            }

            return;
        }

        if (!$existing instanceof MenuItemTranslation) {
            $existing = new MenuItemTranslation();
            $existing->setLocale($locale);
            $item->addTranslation($existing);
            $this->entityManager->persist($existing);
        }

        $existing->setLabel($clean);
        $this->entityManager->flush();
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function validateTarget(MenuItemTargetTypeEnum $targetType, ?int $targetId, ?string $customUrl): void
    {
        if ($targetType->requiresTargetId() && null === $targetId) {
            throw new InvalidArgumentException('admin.menus.errors.target_required');
        }

        if ($targetType->requiresCustomUrl() && (null === $customUrl || '' === mb_trim($customUrl))) {
            throw new InvalidArgumentException('admin.menus.errors.custom_url_required');
        }
    }

    private function nextPosition(Menu $menu, ?MenuItem $parent): int
    {
        $max = -1;
        foreach ($menu->getItems() as $item) {
            if ($item->getParent()?->getId() === $parent?->getId() && $item->getPosition() > $max) {
                $max = $item->getPosition();
            }
        }

        return $max + 1;
    }

    /** Detect if assigning $candidateParent to $item would create a cycle. */
    private function wouldCreateCycle(MenuItem $item, MenuItem $candidateParent): bool
    {
        $cursor = $candidateParent;
        while ($cursor instanceof MenuItem) {
            if ($cursor->getId() === $item->getId()) {
                return true;
            }

            $cursor = $cursor->getParent();
        }

        return false;
    }
}
