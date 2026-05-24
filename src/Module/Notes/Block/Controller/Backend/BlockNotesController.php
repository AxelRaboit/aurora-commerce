<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Notes\Block\Dto\BlockNoteInputFactoryInterface;
use Aurora\Module\Notes\Block\Dto\BlockNoteReorderInputFactoryInterface;
use Aurora\Module\Notes\Block\Entity\BlockNoteInterface;
use Aurora\Module\Notes\Block\Manager\BlockNoteManagerInterface;
use Aurora\Module\Notes\Block\Repository\BlockNoteRepository;
use Aurora\Module\Notes\Block\Serializer\BlockNoteSerializerInterface;
use Aurora\Module\Notes\Block\Service\BlockNoteHierarchyService;
use Aurora\Module\Notes\Block\View\BlockNotesViewBuilder;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/notes/block', name: 'backend_notes_block')]
#[IsGranted('notes.block.use')]
final class BlockNotesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly BlockNoteSerializerInterface $serializer,
        private readonly BlockNoteManagerInterface $manager,
        private readonly BlockNoteRepository $repository,
        private readonly BlockNoteInputFactoryInterface $inputFactory,
        private readonly BlockNoteReorderInputFactoryInterface $reorderInputFactory,
        private readonly PayloadValidator $payloadValidator,
        private readonly BlockNotesViewBuilder $viewBuilder,
        private readonly BlockNoteHierarchyService $hierarchy,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->render('@Notes/backend/block/index.html.twig', $this->viewBuilder->indexView($user));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->jsonSuccess(['notes' => $this->repository->findFlatListForUser($user)]);
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

        return $this->jsonSuccess(['note' => $this->serializer->serializeDetail($note)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $note = $this->repository->findOneByUserAndId($user, $id);
        if (!$note instanceof BlockNoteInterface) {
            return $this->jsonNotFound();
        }

        $input = $this->inputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->manager->update($note, $input);

        return $this->jsonSuccess(['note' => $this->serializer->serializeDetail($note)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $note = $this->repository->findOneByUserAndId($user, $id);
        if (!$note instanceof BlockNoteInterface) {
            return $this->jsonNotFound();
        }

        $this->manager->delete($note);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/move', name: '_move', methods: [HttpMethodEnum::Post->value])]
    public function move(int $id, Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $note = $this->repository->findOneByUserAndId($user, $id);
        if (!$note instanceof BlockNoteInterface) {
            return $this->jsonNotFound();
        }

        $data = $this->decodeJson($request);
        $parentId = isset($data['parentId']) ? (int) $data['parentId'] : null;

        $parent = null;
        if (null !== $parentId) {
            $parent = $this->repository->findOneByUserAndId($user, $parentId);
            if (!$parent instanceof BlockNoteInterface) {
                return $this->jsonNotFound();
            }

            if ($this->hierarchy->wouldCreateCycle($note, $parent)) {
                return $this->jsonFailure('cycle', extra: ['message' => 'Cannot move a note under one of its descendants.']);
            }
        }

        $this->manager->move($note, $parent);

        return $this->jsonSuccess(['note' => $this->serializer->serializeListItem($note)]);
    }

    #[Route('/search', name: '_search', methods: [HttpMethodEnum::Get->value])]
    public function search(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $query = (string) $request->query->get('q', '');

        return $this->jsonSuccess(['ids' => $this->manager->searchContent($user, $query)]);
    }

    #[Route('/reorder', name: '_reorder', methods: [HttpMethodEnum::Post->value])]
    public function reorder(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->reorderInputFactory->fromArray($this->decodeJson($request));

        try {
            $this->manager->reorder($user, $input->entries);
        } catch (InvalidArgumentException) {
            return $this->jsonFailure('cycle', extra: ['message' => 'Reorder would create a cycle.']);
        }

        return $this->jsonSuccess();
    }

    /**
     * Declared after the static GET routes (/list, /search) so the router
     * matches those first — otherwise /{id} with id="search" would shadow them.
     */
    #[Route('/{id}', name: '_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function show(int $id): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $note = $this->repository->findOneByUserAndId($user, $id);
        if (!$note instanceof BlockNoteInterface) {
            return $this->jsonNotFound();
        }

        return $this->jsonSuccess(['note' => $this->serializer->serializeDetail($note)]);
    }
}
