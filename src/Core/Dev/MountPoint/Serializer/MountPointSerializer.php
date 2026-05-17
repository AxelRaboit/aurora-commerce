<?php

declare(strict_types=1);

namespace Aurora\Core\Dev\MountPoint\Serializer;

use Aurora\Core\Dev\MountPoint\Entity\MountPointInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use const DATE_ATOM;

#[AsAlias(MountPointSerializerInterface::class)]
class MountPointSerializer implements MountPointSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(MountPointInterface $mountPoint): array
    {
        return [
            'id' => $mountPoint->getId(),
            'name' => $mountPoint->getName(),
            'type' => $mountPoint->getType()->value,
            'host' => $mountPoint->getHost(),
            'port' => $mountPoint->getPort(),
            'username' => $mountPoint->getUsername(),
            'hasPassword' => null !== $mountPoint->getPassword(),
            'hasSshPrivateKey' => null !== $mountPoint->getSshPrivateKey(),
            'database' => $mountPoint->getDatabase(),
            'sshPublicKey' => $mountPoint->getSshPublicKey(),
            'config' => $mountPoint->getConfig(),
            'lastTestedAt' => $mountPoint->getLastTestedAt()?->format(DATE_ATOM),
            'lastTestSuccessful' => $mountPoint->isLastTestSuccessful(),
            'createdAt' => $mountPoint->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $mountPoint->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
