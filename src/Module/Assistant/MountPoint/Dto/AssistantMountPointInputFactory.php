<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Assistant\MountPoint\Enum\MountPointAccessEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AssistantMountPointInputFactoryInterface::class)]
class AssistantMountPointInputFactory implements AssistantMountPointInputFactoryInterface
{
    public function fromArray(array $data): AssistantMountPointInputInterface
    {
        $access = MountPointAccessEnum::tryFrom(Str::trimFromArray($data, 'access')) ?? MountPointAccessEnum::ReadOnly;

        return new AssistantMountPointInput(
            name: Str::trimFromArray($data, 'name'),
            path: Str::trimFromArray($data, 'path'),
            access: $access,
            active: !isset($data['active']) || (bool) $data['active'],
        );
    }
}
