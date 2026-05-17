<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Backend\View;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\MountPoint\Repository\AssistantMountPointRepository;
use Aurora\Module\Assistant\MountPoint\Serializer\AssistantMountPointSerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AssistantMountPointsViewBuilder
{
    public function __construct(
        private AssistantMountPointRepository $repository,
        private AssistantMountPointSerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user): array
    {
        return [
            'mountPoints' => array_map(
                $this->serializer->serialize(...),
                $this->repository->findForUser($user),
            ),
            'listPath' => $this->urlGenerator->generate('backend_assistant_mount_points_list'),
            'createPath' => $this->urlGenerator->generate('backend_assistant_mount_points_create'),
            'updatePath' => $this->urlGenerator->generate('backend_assistant_mount_points_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_assistant_mount_points_delete', ['id' => '__id__']),
        ];
    }
}
