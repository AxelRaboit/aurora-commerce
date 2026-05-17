<?php

declare(strict_types=1);

namespace Aurora\Module\Configuration\Theme\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ThemeInputFactoryInterface::class)]
class ThemeInputFactory implements ThemeInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ThemeInputInterface
    {
        $config = is_array($data['config'] ?? null) ? $data['config'] : [];

        return new ThemeInput(
            slug: mb_strtolower(Str::trimFromArray($data, 'slug')),
            name: Str::trimFromArray($data, 'name'),
            description: Str::trimOrNullFromArray($data, 'description'),
            config: $config,
        );
    }
}
