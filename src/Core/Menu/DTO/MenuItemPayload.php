<?php

declare(strict_types=1);

namespace App\Core\Menu\DTO;

use App\Core\Menu\Enum\MenuItemTargetTypeEnum;
use App\Core\Menu\Enum\MenuItemVisibilityEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class MenuItemPayload
{
    /**
     * @param array<string, string|null> $translations Locale → label (or null to clear)
     */
    public function __construct(
        #[Assert\NotNull(message: 'admin.menus.errors.target_type_invalid')]
        public ?MenuItemTargetTypeEnum $targetType,
        public ?int $targetId,
        public ?string $customUrl,
        public ?int $parentId,
        public bool $openInNewTab,
        public ?string $cssClass,
        public MenuItemVisibilityEnum $visibility,
        public array $translations,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $translations = [];
        if (isset($data['translations']) && is_array($data['translations'])) {
            foreach ($data['translations'] as $locale => $label) {
                $translations[(string) $locale] = null !== $label ? (string) $label : null;
            }
        }

        $cssClass = isset($data['cssClass']) && '' !== $data['cssClass'] ? (string) $data['cssClass'] : null;

        return new self(
            targetType: MenuItemTargetTypeEnum::tryFrom((string) ($data['targetType'] ?? '')),
            targetId: isset($data['targetId']) ? (int) $data['targetId'] : null,
            customUrl: isset($data['customUrl']) ? (string) $data['customUrl'] : null,
            parentId: isset($data['parentId']) ? (int) $data['parentId'] : null,
            openInNewTab: (bool) ($data['openInNewTab'] ?? false),
            cssClass: $cssClass,
            visibility: MenuItemVisibilityEnum::tryFrom((string) ($data['visibility'] ?? '')) ?? MenuItemVisibilityEnum::Always,
            translations: $translations,
        );
    }

    /**
     * Options array consumed by MenuManager::createItem/updateItem.
     *
     * @return array<string, mixed>
     */
    public function toOptions(): array
    {
        return [
            'customUrl' => $this->customUrl,
            'parentId' => $this->parentId,
            'openInNewTab' => $this->openInNewTab,
            'cssClass' => $this->cssClass,
            'visibility' => $this->visibility,
        ];
    }
}
