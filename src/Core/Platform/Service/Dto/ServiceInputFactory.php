<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Service\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ServiceInputFactoryInterface::class)]
class ServiceInputFactory implements ServiceInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ServiceInputInterface
    {
        return new ServiceInput(
            name: Str::trimFromArray($data, 'name'),
        );
    }
}
