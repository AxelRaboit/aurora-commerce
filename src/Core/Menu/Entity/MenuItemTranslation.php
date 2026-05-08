<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Entity;

use Aurora\Core\Menu\Repository\MenuItemTranslationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuItemTranslationRepository::class)]
#[ORM\Table(name: 'core_menu_item_translations')]
#[ORM\UniqueConstraint(name: 'uniq_menu_item_locale', columns: ['menu_item_id', 'locale'])]
class MenuItemTranslation extends AbstractMenuItemTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_menu_item_translation_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
