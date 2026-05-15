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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    ) {}

    /**
     * Flat list of all the current user's notes (no content). Frontend
     * builds the tree from parent_id + position.
     */
    #[Route('', name: '_index', methods: [HttpMethodEnum::Get->value])]
    public function index(): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->jsonSuccess(['notes' => $this->repository->findFlatListForUser($user)]);
    }

    #[Route('/{id}', name: '_show', methods: [HttpMethodEnum::Get->value], requirements: ['id' => '\d+'])]
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

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value], requirements: ['id' => '\d+'])]
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

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value], requirements: ['id' => '\d+'])]
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

    #[Route('/{id}/move', name: '_move', methods: [HttpMethodEnum::Post->value], requirements: ['id' => '\d+'])]
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

            if ($this->wouldCreateCycle($note, $parent)) {
                return $this->jsonFailure('cycle', extra: ['message' => 'Cannot move a note under one of its descendants.']);
            }
        }

        $this->manager->move($note, $parent);

        return $this->jsonSuccess(['note' => $this->serializer->serializeListItem($note)]);
    }

    #[Route('/reorder', name: '_reorder', methods: [HttpMethodEnum::Post->value])]
    public function reorder(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $data = $this->decodeJson($request);
        $rawIds = $data['ids'] ?? [];
        if (!is_array($rawIds)) {
            return $this->jsonInvalidInput(['ids' => 'must_be_array']);
        }

        $orderedIds = [];
        foreach ($rawIds as $id) {
            if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                $orderedIds[] = (int) $id;
            }
        }

        $this->manager->reorder($user, $orderedIds);

        return $this->jsonSuccess();
    }

    /**
     * Returns true if moving $note under $newParent would create a cycle
     * (i.e. $newParent is $note itself or a descendant of $note).
     */
    private function wouldCreateCycle(MarkdownNoteInterface $note, MarkdownNoteInterface $newParent): bool
    {
        $current = $newParent;
        while (null !== $current) {
            if ($current->getId() === $note->getId()) {
                return true;
            }
            $current = $current->getParent();
        }

        return false;
    }
}
