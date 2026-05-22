<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceCategoryInputFactoryInterface::class)]
class PersonalFinanceCategoryInputFactory implements PersonalFinanceCategoryInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceCategoryInputInterface
    {
        return new PersonalFinanceCategoryInput(
            name: Str::trimFromArray($data, 'name'),
        );
    }
}
