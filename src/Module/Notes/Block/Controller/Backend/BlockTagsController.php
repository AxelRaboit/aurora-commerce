<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Notes\Block\Dto\TagDeleteInputFactoryInterface;
use Aurora\Module\Notes\Block\Dto\TagMergeInputFactoryInterface;
use Aurora\Module\Notes\Block\Dto\TagRenameInputFactoryInterface;
use Aurora\Module\Notes\Block\Manager\BlockNoteManagerInterface;
use Aurora\Module\Notes\Block\Serializer\BlockNoteSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/notes/block/tags', name: 'backend_notes_block_tags')]
#[IsGranted('notes.block.use')]
final class BlockTagsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly BlockNoteManagerInterface $manager,
        private readonly BlockNoteSerializerInterface $serializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly TagRenameInputFactoryInterface $tagRenameFactory,
        private readonly TagMergeInputFactoryInterface $tagMergeFactory,
        private readonly TagDeleteInputFactoryInterface $tagDeleteFactory,
    ) {}

    #[Route('', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->jsonSuccess([
            'tags' => $this->serializer->serializeTagCounts($this->manager->tagCounts($user)),
        ]);
    }

    #[Route('/rename', name: '_rename', methods: [HttpMethodEnum::Post->value])]
    public function rename(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->tagRenameFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        return $this->jsonSuccess(['affected' => $this->manager->renameTag($user, $input->oldTag, $input->newTag)]);
    }

    #[Route('/merge', name: '_merge', methods: [HttpMethodEnum::Post->value])]
    public function merge(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->tagMergeFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        return $this->jsonSuccess(['affected' => $this->manager->mergeTags($user, $input->sourceTags, $input->targetTag)]);
    }

    #[Route('/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->tagDeleteFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        return $this->jsonSuccess(['affected' => $this->manager->removeTag($user, $input->tag)]);
    }
}
