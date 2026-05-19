<?php

declare(strict_types=1);

namespace Aurora\Module\Dev\MountPoint\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Dev\MountPoint\Enum\MountPointTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MountPointInputFactoryInterface::class)]
class MountPointInputFactory implements MountPointInputFactoryInterface
{
    public function fromArray(array $data): MountPointInputInterface
    {
        $port = $data['port'] ?? null;
        $config = isset($data['config']) && is_array($data['config']) ? $data['config'] : [];

        return new MountPointInput(
            name: Str::trimFromArray($data, 'name'),
            type: MountPointTypeEnum::from(Str::trimFromArray($data, 'type', MountPointTypeEnum::Database->value)),
            host: Str::trimFromArray($data, 'host'),
            port: is_numeric($port) ? (int) $port : null,
            username: Str::trimOrNullFromArray($data, 'username'),
            password: Str::trimOrNullFromArray($data, 'password'),
            database: Str::trimOrNullFromArray($data, 'database'),
            sshPublicKey: Str::trimOrNullFromArray($data, 'sshPublicKey'),
            sshPrivateKey: Str::trimOrNullFromArray($data, 'sshPrivateKey'),
            config: $config,
        );
    }
}
