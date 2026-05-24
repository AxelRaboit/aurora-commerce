<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Notes\PostIt\Dto\PostItNoteInputFactoryInterface;
use Aurora\Module\Notes\PostIt\Entity\PostItNoteInterface;
use Aurora\Module\Notes\PostIt\Manager\PostItNoteManagerInterface;
use Aurora\Module\Notes\PostIt\Repository\PostItNoteRepository;
use Aurora\Module\Notes\PostIt\Serializer\PostItNoteSerializerInterface;
use Aurora\Module\Notes\PostIt\View\PostItNotesViewBuilder;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/notes/post-it', name: 'backend_notes_post_it')]
#[IsGranted('notes.post_it.use')]
final class PostItNotesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PostItNoteSerializerInterface $serializer,
        private readonly PostItNoteManagerInterface $manager,
        private readonly PostItNoteRepository $repository,
        private readonly PostItNoteInputFactoryInterface $inputFactory,
        private readonly PayloadValidator $payloadValidator,
        private readonly PostItNotesViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Notes/backend/post_it/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $notes = array_map(
            $this->serializer->serialize(...),
            $this->repository->findAllForUser($user),
        );

        return $this->jsonSuccess(['notes' => $notes]);
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

        $note = $this->manager->create($user, $input);

        return $this->jsonSuccess(['note' => $this->serializer->serialize($note)]);
    }

    #[Route('/{id}/update', name: '_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $note = $this->repository->findOneByUserAndId($user, $id);
        if (!$note instanceof PostItNoteInterface) {
            return $this->jsonNotFound();
        }

        $input = $this->inputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->manager->update($note, $input);

        return $this->jsonSuccess(['note' => $this->serializer->serialize($note)]);
    }

    #[Route('/{id}/move', name: '_move', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    public function move(int $id, Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $note = $this->repository->findOneByUserAndId($user, $id);
        if (!$note instanceof PostItNoteInterface) {
            return $this->jsonNotFound();
        }

        $data = $this->decodeJson($request);
        $positionX = isset($data['positionX']) ? (int) $data['positionX'] : $note->getPositionX();
        $positionY = isset($data['positionY']) ? (int) $data['positionY'] : $note->getPositionY();

        $this->manager->move($note, $positionX, $positionY);

        return $this->jsonSuccess(['note' => $this->serializer->serialize($note)]);
    }

    #[Route('/{id}/resize', name: '_resize', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    public function resize(int $id, Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $note = $this->repository->findOneByUserAndId($user, $id);
        if (!$note instanceof PostItNoteInterface) {
            return $this->jsonNotFound();
        }

        $data = $this->decodeJson($request);
        $width = isset($data['width']) ? (int) $data['width'] : $note->getWidth();
        $height = isset($data['height']) ? (int) $data['height'] : $note->getHeight();

        $this->manager->resize($note, $width, $height);

        return $this->jsonSuccess(['note' => $this->serializer->serialize($note)]);
    }

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $note = $this->repository->findOneByUserAndId($user, $id);
        if (!$note instanceof PostItNoteInterface) {
            return $this->jsonNotFound();
        }

        $this->manager->delete($note);

        return $this->jsonSuccess();
    }
}
