<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Backend\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Assistant\Backend\View\AssistantMountPointsViewBuilder;
use Aurora\Module\Assistant\MountPoint\Dto\AssistantMountPointInputFactoryInterface;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPointInterface;
use Aurora\Module\Assistant\MountPoint\Manager\AssistantMountPointManagerInterface;
use Aurora\Module\Assistant\MountPoint\Repository\AssistantMountPointRepository;
use Aurora\Module\Assistant\MountPoint\Serializer\AssistantMountPointSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/assistant/mount-points', name: 'backend_assistant_mount_points')]
#[IsGranted('assistant.use')]
final class AssistantMountPointsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly AssistantMountPointManagerInterface $manager,
        private readonly AssistantMountPointRepository $repository,
        private readonly AssistantMountPointSerializerInterface $serializer,
        private readonly AssistantMountPointInputFactoryInterface $inputFactory,
        private readonly PayloadValidator $payloadValidator,
        private readonly AssistantMountPointsViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->render('@Assistant/backend/mount-points.html.twig', $this->viewBuilder->indexView($user));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->jsonSuccess([
            'mountPoints' => array_map(
                $this->serializer->serialize(...),
                $this->repository->findForUser($user),
            ),
        ]);
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->inputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $mountPoint = $this->manager->create($user, $input);

        return $this->jsonSuccess(['mountPoint' => $this->serializer->serialize($mountPoint)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(int $id, Request $request): JsonResponse
    {
        $mountPoint = $this->resolveOrNull($id);
        if (!$mountPoint instanceof AssistantMountPointInterface) {
            return $this->jsonNotFound();
        }

        $input = $this->inputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->manager->update($mountPoint, $input);

        return $this->jsonSuccess(['mountPoint' => $this->serializer->serialize($mountPoint)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        $mountPoint = $this->resolveOrNull($id);
        if (!$mountPoint instanceof AssistantMountPointInterface) {
            return $this->jsonNotFound();
        }

        $this->manager->delete($mountPoint);

        return $this->jsonSuccess();
    }

    private function resolveOrNull(int $id): ?AssistantMountPointInterface
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->repository->findOneByUserAndId($user, $id);
    }
}
