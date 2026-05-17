<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Backend\View;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\Conversation\Repository\ConversationRepository;
use Aurora\Module\Assistant\Llm\Contract\ChatClientInterface;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPointInterface;
use Aurora\Module\Assistant\MountPoint\Repository\AssistantMountPointRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AssistantViewBuilder
{
    public function __construct(
        private ConversationRepository $conversationRepository,
        private AssistantMountPointRepository $mountPointRepository,
        private ChatClientInterface $chatClient,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user): array
    {
        $mountPoints = array_map(
            static fn (AssistantMountPointInterface $mp): array => [
                'id' => $mp->getId(),
                'name' => $mp->getName(),
                'path' => $mp->getPath(),
                'access' => $mp->getAccess()->value,
            ],
            $this->mountPointRepository->findActiveForUser($user),
        );

        return [
            'conversations' => $this->conversationRepository->findListForUser($user),
            'mountPoints' => $mountPoints,
            'model' => $this->chatClient->getModel(),
            'listPath' => $this->urlGenerator->generate('backend_assistant_chat_list'),
            'showPath' => $this->urlGenerator->generate('backend_assistant_chat_show', ['id' => '__id__']),
            'createPath' => $this->urlGenerator->generate('backend_assistant_chat_create'),
            'sendPath' => $this->urlGenerator->generate('backend_assistant_chat_send', ['id' => '__id__']),
            'confirmToolPath' => $this->urlGenerator->generate('backend_assistant_chat_confirm_tool', ['id' => '__id__']),
            'renamePath' => $this->urlGenerator->generate('backend_assistant_chat_rename', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_assistant_chat_delete', ['id' => '__id__']),
        ];
    }
}
