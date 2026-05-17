<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Backend\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Assistant\Backend\View\AssistantViewBuilder;
use Aurora\Module\Assistant\Conversation\Dto\MessageInputFactoryInterface;
use Aurora\Module\Assistant\Conversation\Entity\ConversationInterface;
use Aurora\Module\Assistant\Conversation\Manager\ConversationManagerInterface;
use Aurora\Module\Assistant\Conversation\Repository\ConversationRepository;
use Aurora\Module\Assistant\Conversation\Serializer\ConversationSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function is_array;
use function is_string;

#[Route('/backend/assistant/chat', name: 'backend_assistant_chat')]
#[IsGranted('assistant.use')]
final class AssistantController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly ConversationManagerInterface $manager,
        private readonly ConversationRepository $repository,
        private readonly ConversationSerializerInterface $serializer,
        private readonly MessageInputFactoryInterface $inputFactory,
        private readonly PayloadValidator $payloadValidator,
        private readonly AssistantViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->render('@Assistant/backend/index.html.twig', $this->viewBuilder->indexView($user));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->jsonSuccess(['conversations' => $this->repository->findListForUser($user)]);
    }

    #[Route('/{id}/show', name: '_show', methods: [HttpMethodEnum::Get->value])]
    public function show(int $id): JsonResponse
    {
        $conversation = $this->resolveOrNull($id);
        if (!$conversation instanceof ConversationInterface) {
            return $this->jsonNotFound();
        }

        return $this->jsonSuccess(['conversation' => $this->serializer->serializeDetail($conversation)]);
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $conversation = $this->manager->create($user);

        return $this->jsonSuccess(['conversation' => $this->serializer->serializeDetail($conversation)]);
    }

    #[Route('/{id}/send', name: '_send', methods: [HttpMethodEnum::Post->value])]
    public function send(int $id, Request $request): JsonResponse
    {
        $conversation = $this->resolveOrNull($id);
        if (!$conversation instanceof ConversationInterface) {
            return $this->jsonNotFound();
        }

        $input = $this->inputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->manager->sendMessage($conversation, $input);

        return $this->jsonSuccess(['conversation' => $this->serializer->serializeDetail($conversation)]);
    }

    #[Route('/{id}/confirm-tool', name: '_confirm_tool', methods: [HttpMethodEnum::Post->value])]
    public function confirmTool(int $id, Request $request): JsonResponse
    {
        $conversation = $this->resolveOrNull($id);
        if (!$conversation instanceof ConversationInterface) {
            return $this->jsonNotFound();
        }

        $payload = $this->decodeJson($request);
        $rawDecisions = $payload['decisions'] ?? [];
        $decisions = [];
        if (is_array($rawDecisions)) {
            foreach ($rawDecisions as $key => $value) {
                if (is_string($key) && is_string($value)) {
                    $decisions[$key] = $value;
                }
            }
        }

        $this->manager->resumeAfterConfirmation($conversation, $decisions);

        return $this->jsonSuccess(['conversation' => $this->serializer->serializeDetail($conversation)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        $conversation = $this->resolveOrNull($id);
        if (!$conversation instanceof ConversationInterface) {
            return $this->jsonNotFound();
        }

        $this->manager->delete($conversation);

        return $this->jsonSuccess();
    }

    private function resolveOrNull(int $id): ?ConversationInterface
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->repository->findOneByUserAndId($user, $id);
    }
}
