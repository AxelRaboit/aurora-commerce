<?php

declare(strict_types=1);

namespace App\DTO;

use App\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ThemeInput
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        #[Assert\NotBlank(message: 'themes.errors.slug_required')]
        #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'themes.errors.slug_invalid')]
        #[Assert\Length(max: 100)]
        public string $slug,
        #[Assert\NotBlank(message: 'themes.errors.name_required')]
        #[Assert\Length(max: 200)]
        public string $name,
        public ?string $description,
        public array $config,
    ) {}

    public static function fromArray(array $data): self
    {
        $config = is_array($data['config'] ?? null) ? $data['config'] : [];

        return new self(
            slug: mb_strtolower(Str::trimOrNull((string) ($data['slug'] ?? '')) ?? ''),
            name: Str::trimOrNull((string) ($data['name'] ?? '')) ?? '',
            description: Str::trimOrNull((string) ($data['description'] ?? '')),
            config: $config,
        );
    }
}
