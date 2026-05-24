<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultFolder\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Vault\VaultFolder\Dto\VaultFolderInputFactoryInterface;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;
use Aurora\Module\Vault\VaultFolder\Manager\VaultFolderManagerInterface;
use Aurora\Module\Vault\VaultFolder\Repository\VaultFolderRepository;
use Aurora\Module\Vault\VaultFolder\Serializer\VaultFolderSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/vault/folders', name: 'backend_vault_folders')]
#[IsGranted('vault.use')]
final class VaultFoldersController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly VaultFolderSerializerInterface $vaultFolderSerializer,
        private readonly VaultFolderManagerInterface $vaultFolderManager,
        private readonly VaultFolderRepository $vaultFolderRepository,
        private readonly PayloadValidator $payloadValidator,
        private readonly VaultFolderInputFactoryInterface $vaultFolderInputFactory,
    ) {}

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $input = $this->vaultFolderInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $folder = $this->vaultFolderManager->create($user, $input);

        return $this->jsonSuccess(['folder' => $this->vaultFolderSerializer->serialize($folder)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $folder = $this->vaultFolderRepository->findOneByUserAndId($user, $id);
        if (!$folder instanceof VaultFolderInterface) {
            return $this->jsonNotFound();
        }

        $input = $this->vaultFolderInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->vaultFolderManager->update($folder, $input);

        return $this->jsonSuccess(['folder' => $this->vaultFolderSerializer->serialize($folder)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(int $id): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $folder = $this->vaultFolderRepository->findOneByUserAndId($user, $id);
        if (!$folder instanceof VaultFolderInterface) {
            return $this->jsonNotFound();
        }

        $this->vaultFolderManager->delete($folder);

        return $this->jsonSuccess();
    }
}
