<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Markdown\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Notes\Markdown\Dto\MarkdownNoteInputFactoryInterface;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNoteInterface;
use Aurora\Module\Notes\Markdown\Manager\MarkdownNoteManagerInterface;
use Aurora\Module\Notes\Markdown\Repository\MarkdownNoteRepository;
use Aurora\Module\Notes\Markdown\Serializer\MarkdownNoteSerializerInterface;
use Aurora\Module\Notes\Markdown\Service\MarkdownNoteHierarchyService;
use Aurora\Module\Notes\Markdown\View\MarkdownNotesViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/notes/markdown', name: 'backend_notes_markdown')]
#[IsGranted('notes.markdown.use')]
final class MarkdownNotesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly MarkdownNoteSerializerInterface $serializer,
        private readonly MarkdownNoteManagerInterface $manager,
        private readonly MarkdownNoteRepository $repository,
        private readonly MarkdownNoteInputFactoryInterface $inputFactory,
        private readonly PayloadValidator $payloadValidator,
        private readonly MarkdownNotesViewBuilder $viewBuilder,
        private readonly MarkdownNoteHierarchyService $hierarchy,
    ) {}

    /**
     * Backend page render — mounts the Vue MarkdownNotesApp with the URL
     * map preloaded by the view builder. Initial note list is fetched
     * client-side via the JSON list endpoint.
     */
    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->render('@Notes/backend/markdown/index.html.twig', $this->viewBuilder->indexView($user));
    }

    /**
     * Flat list of all the current user's notes (no content). The Vue
     * frontend rebuilds the tree from parent_id + position.
     */
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
        if (!$note instanceof MarkdownNoteInterface) {
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
        if (!$note instanceof MarkdownNoteInterface) {
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
        if (!$note instanceof MarkdownNoteInterface) {
            return $this->jsonNotFound();
        }

        $data = $this->decodeJson($request);
        $parentId = isset($data['parentId']) ? (int) $data['parentId'] : null;

        $parent = null;
        if (null !== $parentId) {
            $parent = $this->repository->findOneByUserAndId($user, $parentId);
            if (!$parent instanceof MarkdownNoteInterface) {
                return $this->jsonNotFound();
            }

            if ($this->hierarchy->wouldCreateCycle($note, $parent)) {
                return $this->jsonFailure('cycle', extra: ['message' => 'Cannot move a note under one of its descendants.']);
            }
        }

        $this->manager->move($note, $parent);

        return $this->jsonSuccess(['note' => $this->serializer->serializeListItem($note)]);
    }

    #[Route('/{id}/backlinks', name: '_backlinks', methods: [HttpMethodEnum::Get->value])]
    public function backlinks(int $id): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $note = $this->repository->findOneByUserAndId($user, $id);
        if (!$note instanceof MarkdownNoteInterface) {
            return $this->jsonNotFound();
        }

        return $this->jsonSuccess(['backlinks' => $this->manager->backlinks($user, $note)]);
    }

    #[Route('/{id}/unlinked-mentions', name: '_unlinked_mentions', methods: [HttpMethodEnum::Get->value])]
    public function unlinkedMentions(int $id): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $note = $this->repository->findOneByUserAndId($user, $id);
        if (!$note instanceof MarkdownNoteInterface) {
            return $this->jsonNotFound();
        }

        return $this->jsonSuccess(['mentions' => $this->manager->unlinkedMentions($user, $note)]);
    }

    #[Route('/graph', name: '_graph', methods: [HttpMethodEnum::Get->value])]
    public function graph(): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->jsonSuccess($this->manager->graph($user));
    }

    #[Route('/reorder', name: '_reorder', methods: [HttpMethodEnum::Post->value])]
    public function reorder(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $data = $this->decodeJson($request);
        $rawEntries = $data['entries'] ?? [];
        if (!is_array($rawEntries)) {
            return $this->jsonInvalidInput(['entries' => 'must_be_array']);
        }

        $entries = [];
        foreach ($rawEntries as $entry) {
            if (!is_array($entry) || !isset($entry['id'])) {
                continue;
            }
            $entries[] = [
                'id' => (int) $entry['id'],
                'parentId' => isset($entry['parentId']) && '' !== $entry['parentId'] && null !== $entry['parentId']
                    ? (int) $entry['parentId']
                    : null,
                'position' => (int) ($entry['position'] ?? 0),
            ];
        }

        try {
            $this->manager->reorder($user, $entries);
        } catch (\InvalidArgumentException) {
            return $this->jsonFailure('cycle', extra: ['message' => 'Reorder would create a cycle.']);
        }

        return $this->jsonSuccess();
    }

    /**
     * Declared after the static GET routes (/list, /graph) so the router
     * matches those first — otherwise /{id} with id="graph" would shadow them.
     */
    #[Route('/{id}', name: '_show', methods: [HttpMethodEnum::Get->value])]
    public function show(int $id): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $note = $this->repository->findOneByUserAndId($user, $id);
        if (!$note instanceof MarkdownNoteInterface) {
            return $this->jsonNotFound();
        }

        return $this->jsonSuccess(['note' => $this->serializer->serializeDetail($note)]);
    }
}
