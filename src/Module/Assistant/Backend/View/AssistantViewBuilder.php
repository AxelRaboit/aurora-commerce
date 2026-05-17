<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Backend\View;

use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\Conversation\Repository\ConversationRepository;
use Aurora\Module\Assistant\Llm\Contract\ChatClientInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AssistantViewBuilder
{
    public function __construct(
        private ConversationRepository $conversationRepository,
        private ChatClientInterface $chatClient,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user): array
    {
        return [
            'conversations' => $this->conversationRepository->findListForUser($user),
            'model' => $this->chatClient->getModel(),
            'listPath' => $this->urlGenerator->generate('backend_assistant_list'),
            'showPath' => $this->urlGenerator->generate('backend_assistant_show', ['id' => '__id__']),
            'createPath' => $this->urlGenerator->generate('backend_assistant_create'),
            'sendPath' => $this->urlGenerator->generate('backend_assistant_send', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_assistant_delete', ['id' => '__id__']),
        ];
    }
}
