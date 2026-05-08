<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AgencyInputFactoryInterface::class)]
class AgencyInputFactory implements AgencyInputFactoryInterface
{
    public function fromArray(array $data): AgencyInputInterface
    {
        return new AgencyInput(name: Str::trimFromArray($data, 'name'));
    }
}
