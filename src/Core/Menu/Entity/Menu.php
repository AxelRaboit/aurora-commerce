<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Entity;

use Aurora\Core\Menu\Repository\MenuRepository;
use Aurora\Core\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
#[ORM\Table(name: 'core_menus')]
#[ORM\HasLifecycleCallbacks]
class Menu
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_menu_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 100, unique: true)]
    private string $location;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: MenuItem::class, mappedBy: 'menu', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => Order::Ascending->value])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /** @return Collection<int, MenuItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(MenuItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setMenu($this);
        }

        return $this;
    }

    public function removeItem(MenuItem $item): static
    {
        $this->items->removeElement($item);

        return $this;
    }
}
