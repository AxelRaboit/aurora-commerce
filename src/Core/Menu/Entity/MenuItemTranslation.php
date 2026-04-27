<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Entity;

use Aurora\Core\Menu\Repository\MenuItemTranslationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuItemTranslationRepository::class)]
#[ORM\Table(name: 'menu_item_translations')]
#[ORM\UniqueConstraint(name: 'uniq_menu_item_locale', columns: ['menu_item_id', 'locale'])]
class MenuItemTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MenuItem::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private MenuItem $menuItem;

    #[ORM\Column(length: 10)]
    private string $locale;

    /**
     * Optional override for the auto-resolved label (post title, term name…).
     * Null means: use the target's own label as displayed text.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMenuItem(): MenuItem
    {
        return $this->menuItem;
    }

    public function setMenuItem(MenuItem $menuItem): static
    {
        $this->menuItem = $menuItem;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }
}
