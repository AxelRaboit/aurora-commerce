<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ContactTagInputFactoryInterface::class)]
class ContactTagInputFactory implements ContactTagInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ContactTagInputInterface
    {
        $color = Str::trimFromArray($data, 'color', '#6366F1');
        if ('' === $color) {
            $color = '#6366F1';
        }

        return new ContactTagInput(
            label: Str::trimFromArray($data, 'label'),
            slug: Str::trimOrNullFromArray($data, 'slug'),
            color: $color,
        );
    }
}
