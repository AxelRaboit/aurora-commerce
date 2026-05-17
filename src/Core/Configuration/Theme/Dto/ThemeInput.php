<?php

declare(strict_types=1);

namespace Aurora\Core\Configuration\Theme\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ThemeInput implements ThemeInputInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        #[Assert\NotBlank(message: 'themes.errors.slug_required')]
        #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'themes.errors.slug_invalid')]
        #[Assert\Length(max: 100)]
        public readonly string $slug,
        #[Assert\NotBlank(message: 'themes.errors.name_required')]
        #[Assert\Length(max: 200)]
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $config,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
