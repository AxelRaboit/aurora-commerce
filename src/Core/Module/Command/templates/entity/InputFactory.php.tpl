<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias({{NAME}}InputFactoryInterface::class)]
class {{NAME}}InputFactory implements {{NAME}}InputFactoryInterface
{
    public function fromArray(array $data): {{NAME}}InputInterface
    {
        return new {{NAME}}Input(name: Str::trimFromArray($data, 'name'));
    }
}
