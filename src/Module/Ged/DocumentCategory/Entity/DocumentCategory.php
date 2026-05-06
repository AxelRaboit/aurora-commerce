<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\Entity;

use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\Ged\DocumentCategory\Repository\DocumentCategoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentCategoryRepository::class)]
#[ORM\Table(name: 'backend_ged_document_categories')]
#[ORM\HasLifecycleCallbacks]
class DocumentCategory
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_ged_category_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(length: 180, unique: true)]
    private string $slug;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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
}
