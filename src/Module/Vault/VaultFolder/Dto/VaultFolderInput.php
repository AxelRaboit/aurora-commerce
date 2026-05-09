<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultFolder\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class VaultFolderInput implements VaultFolderInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'vault.folders.errors.name_required')]
        #[Assert\Length(max: 100)]
        public readonly string $name = '',
        #[Assert\Length(max: 7)]
        #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/', message: 'vault.folders.errors.color_invalid')]
        public readonly ?string $color = null,
        public readonly int $position = 0,
        public readonly ?int $parentId = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }
}
