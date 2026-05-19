<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultUserConfig\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Vault\VaultUserConfig\Dto\VaultUserConfigInputFactoryInterface;
use Aurora\Module\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;
use Aurora\Module\Vault\VaultUserConfig\Manager\VaultUserConfigManagerInterface;
use Aurora\Module\Vault\VaultUserConfig\Repository\VaultUserConfigRepository;
use Aurora\Module\Vault\VaultUserConfig\Serializer\VaultUserConfigSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/vault/config', name: 'backend_vault_config')]
#[IsGranted('vault.use')]
final class VaultUserConfigController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly VaultUserConfigSerializerInterface $vaultUserConfigSerializer,
        private readonly VaultUserConfigManagerInterface $vaultUserConfigManager,
        private readonly VaultUserConfigRepository $vaultUserConfigRepository,
        private readonly PayloadValidator $payloadValidator,
        private readonly VaultUserConfigInputFactoryInterface $vaultUserConfigInputFactory,
    ) {}

    #[Route('/setup', name: '_setup', methods: [HttpMethodEnum::Post->value])]
    public function setup(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        if ($this->vaultUserConfigRepository->findOneByUser($user) instanceof VaultUserConfigInterface) {
            return $this->jsonFailure('vault.already_configured', HttpStatusEnum::Conflict->value);
        }

        $input = $this->vaultUserConfigInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $config = $this->vaultUserConfigManager->setup($user, $input);

        return $this->jsonSuccess(['config' => $this->vaultUserConfigSerializer->serialize($config)]);
    }

    #[Route('/change-master-password', name: '_change_master_password', methods: [HttpMethodEnum::Post->value])]
    public function changeMasterPassword(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $config = $this->vaultUserConfigRepository->findOneByUser($user);
        if (!$config instanceof VaultUserConfigInterface) {
            return $this->jsonNotFound();
        }

        $data = $this->decodeJson($request);
        $newSalt = $data['argon2Salt'] ?? '';
        $entries = $data['entries'] ?? [];

        if ('' === $newSalt || !is_array($entries)) {
            return $this->jsonFailure('invalid_payload', HttpStatusEnum::BadRequest->value);
        }

        $this->vaultUserConfigManager->changeMasterPassword($config, $newSalt, $entries);

        return $this->jsonSuccess(['config' => $this->vaultUserConfigSerializer->serialize($config)]);
    }

    #[Route('/destroy', name: '_destroy', methods: [HttpMethodEnum::Post->value])]
    public function destroy(): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $config = $this->vaultUserConfigRepository->findOneByUser($user);
        if (!$config instanceof VaultUserConfigInterface) {
            return $this->jsonNotFound();
        }

        $this->vaultUserConfigManager->destroyVault($user, $config);

        return $this->jsonSuccess([]);
    }
}
