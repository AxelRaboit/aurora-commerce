<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;

interface DocumentCategoryInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getName(): string;

    public function setName(string $name): static;

    public function getSlug(): string;

    public function setSlug(string $slug): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;
}
