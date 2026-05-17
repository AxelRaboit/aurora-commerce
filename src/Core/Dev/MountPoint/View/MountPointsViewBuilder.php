<?php

declare(strict_types=1);

namespace Aurora\Core\Dev\MountPoint\View;

use Aurora\Core\Dev\MountPoint\Enum\MountPointTypeEnum;
use Aurora\Core\Dev\MountPoint\Repository\MountPointRepository;
use Aurora\Core\Dev\MountPoint\Serializer\MountPointSerializerInterface;

final readonly class MountPointsViewBuilder
{
    public function __construct(
        private MountPointRepository $mountPointRepository,
        private MountPointSerializerInterface $mountPointSerializer,
    ) {}

    /** @return array<string, mixed> */
    public function listPayload(): array
    {
        $mountPoints = array_map(
            $this->mountPointSerializer->serialize(...),
            $this->mountPointRepository->findAllOrderedByName(),
        );

        $types = array_map(
            static fn (MountPointTypeEnum $type): array => ['value' => $type->value, 'label' => $type->getLabel()],
            MountPointTypeEnum::cases(),
        );

        return ['mountPoints' => $mountPoints, 'types' => $types];
    }

    /** @param array<string, mixed> $payload */
    public function indexView(array $payload): array
    {
        return [
            'tab' => 'mount_points',
            'mountPoints' => $payload,
        ];
    }
}
