<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Entity;

use Aurora\Core\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Core\Menu\Enum\MenuItemVisibilityEnum;
use Aurora\Core\Menu\Repository\MenuItemRepository;
use Aurora\Core\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuItemRepository::class)]
#[ORM\Table(name: 'menu_items')]
#[ORM\HasLifecycleCallbacks]
class MenuItem
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_menu_item_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 30, enumType: MenuItemTargetTypeEnum::class)]
    private MenuItemTargetTypeEnum $targetType = MenuItemTargetTypeEnum::CustomUrl;

    /**
     * Soft FK: ID of the target post / term / post_type. No physical FK
     * so we don't cascade-delete the menu item if the target disappears —
     * the renderer handles missing targets gracefully.
     */
    #[ORM\Column(nullable: true)]
    private ?int $targetId = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $customUrl = null;

    #[ORM\Column]
    private bool $openInNewTab = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cssClass = null;

    #[ORM\Column(length: 30, enumType: MenuItemVisibilityEnum::class)]
    private MenuItemVisibilityEnum $visibility = MenuItemVisibilityEnum::Always;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\ManyToOne(targetEntity: MenuItem::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?MenuItem $parent = null;

    #[ORM\OneToMany(targetEntity: MenuItem::class, mappedBy: 'parent', cascade: ['remove'])]
    #[ORM\OrderBy(['position' => Order::Ascending->value])]
    private Collection $children;

    #[ORM\ManyToOne(targetEntity: Menu::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Menu $menu;

    /** @var Collection<string, MenuItemTranslation> */
    #[ORM\OneToMany(targetEntity: MenuItemTranslation::class, mappedBy: 'menuItem', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    private Collection $translations;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getTargetType(): MenuItemTargetTypeEnum
    {
        return $this->targetType;
    }

    public function setTargetType(MenuItemTargetTypeEnum $targetType): static
    {
        $this->targetType = $targetType;

        return $this;
    }

    public function getTargetId(): ?int
    {
        return $this->targetId;
    }

    public function setTargetId(?int $targetId): static
    {
        $this->targetId = $targetId;

        return $this;
    }

    public function getCustomUrl(): ?string
    {
        return $this->customUrl;
    }

    public function setCustomUrl(?string $customUrl): static
    {
        $this->customUrl = $customUrl;

        return $this;
    }

    public function isOpenInNewTab(): bool
    {
        return $this->openInNewTab;
    }

    public function setOpenInNewTab(bool $openInNewTab): static
    {
        $this->openInNewTab = $openInNewTab;

        return $this;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function setCssClass(?string $cssClass): static
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function getVisibility(): MenuItemVisibilityEnum
    {
        return $this->visibility;
    }

    public function setVisibility(MenuItemVisibilityEnum $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getParent(): ?MenuItem
    {
        return $this->parent;
    }

    public function setParent(?MenuItem $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /** @return Collection<int, MenuItem> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getMenu(): Menu
    {
        return $this->menu;
    }

    public function setMenu(Menu $menu): static
    {
        $this->menu = $menu;

        return $this;
    }

    /** @return Collection<string, MenuItemTranslation> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?MenuItemTranslation
    {
        return $this->translations->get($locale);
    }

    public function addTranslation(MenuItemTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->set($translation->getLocale(), $translation);
            $translation->setMenuItem($this);
        }

        return $this;
    }

    public function removeTranslation(MenuItemTranslation $translation): static
    {
        $this->translations->removeElement($translation);

        return $this;
    }
}
