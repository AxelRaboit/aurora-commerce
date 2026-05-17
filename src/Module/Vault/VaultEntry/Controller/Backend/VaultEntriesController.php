<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultEntry\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Vault\VaultEntry\Dto\VaultEntryInputFactoryInterface;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntryInterface;
use Aurora\Module\Vault\VaultEntry\Manager\VaultEntryManagerInterface;
use Aurora\Module\Vault\VaultEntry\Repository\VaultEntryRepository;
use Aurora\Module\Vault\VaultEntry\Serializer\VaultEntrySerializerInterface;
use Aurora\Module\Vault\VaultEntry\View\VaultEntriesViewBuilder;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;
use Aurora\Module\Vault\VaultFolder\Repository\VaultFolderRepository;
use Aurora\Module\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;
use Aurora\Module\Vault\VaultUserConfig\Repository\VaultUserConfigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/vault', name: 'backend_vault')]
#[IsGranted('vault.use')]
final class VaultEntriesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly VaultEntrySerializerInterface $vaultEntrySerializer,
        private readonly VaultEntryManagerInterface $vaultEntryManager,
        private readonly VaultEntryRepository $vaultEntryRepository,
        private readonly VaultFolderRepository $vaultFolderRepository,
        private readonly VaultUserConfigRepository $vaultUserConfigRepository,
        private readonly PayloadValidator $payloadValidator,
        private readonly VaultEntriesViewBuilder $viewBuilder,
        private readonly VaultEntryInputFactoryInterface $vaultEntryInputFactory,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->render('@Vault/backend/vault/index.html.twig', $this->viewBuilder->indexView($user));
    }

    #[Route('/entries/create', name: '_entries_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        if (!$this->vaultUserConfigRepository->findOneByUser($user) instanceof VaultUserConfigInterface) {
            return $this->jsonForbidden();
        }

        $input = $this->vaultEntryInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $entry = $this->vaultEntryManager->create($user, $input);

        return $this->jsonSuccess(['entry' => $this->vaultEntrySerializer->serialize($entry)]);
    }

    #[Route('/entries/{id}/update', name: '_entries_update', methods: [HttpMethodEnum::Post->value])]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $entry = $this->vaultEntryRepository->findOneByUserAndId($user, $id);
        if (!$entry instanceof VaultEntryInterface) {
            return $this->jsonNotFound();
        }

        $input = $this->vaultEntryInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->vaultEntryManager->update($entry, $input);

        return $this->jsonSuccess(['entry' => $this->vaultEntrySerializer->serialize($entry)]);
    }

    #[Route('/entries/{id}/delete', name: '_entries_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $entry = $this->vaultEntryRepository->findOneByUserAndId($user, $id);
        if (!$entry instanceof VaultEntryInterface) {
            return $this->jsonNotFound();
        }

        $this->vaultEntryManager->delete($entry);

        return $this->jsonSuccess();
    }

    #[Route('/entries/{id}/toggle-favorite', name: '_entries_toggle_favorite', methods: [HttpMethodEnum::Post->value])]
    public function toggleFavorite(int $id): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $entry = $this->vaultEntryRepository->findOneByUserAndId($user, $id);
        if (!$entry instanceof VaultEntryInterface) {
            return $this->jsonNotFound();
        }

        $this->vaultEntryManager->toggleFavorite($entry);

        return $this->jsonSuccess(['isFavorite' => $entry->isFavorite()]);
    }

    #[Route('/entries/{id}/move', name: '_entries_move', methods: [HttpMethodEnum::Post->value])]
    public function move(int $id, Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $entry = $this->vaultEntryRepository->findOneByUserAndId($user, $id);
        if (!$entry instanceof VaultEntryInterface) {
            return $this->jsonNotFound();
        }

        $data = $this->decodeJson($request);
        $folderId = isset($data['folderId']) ? (int) $data['folderId'] : null;

        $folder = null;
        if (null !== $folderId) {
            $folder = $this->vaultFolderRepository->findOneByUserAndId($user, $folderId);
            if (!$folder instanceof VaultFolderInterface) {
                return $this->jsonNotFound();
            }
        }

        $this->vaultEntryManager->move($entry, $folder);

        return $this->jsonSuccess(['entry' => $this->vaultEntrySerializer->serialize($entry)]);
    }
}
