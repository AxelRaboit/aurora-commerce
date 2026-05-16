<?php

declare(strict_types=1);

namespace Aurora\Core\User\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Manager\UserManagerInterface;
use Aurora\Core\User\Serializer\UserSerializerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * User permissions sub-domain — privileges (per-action allowlist, dev
 * only) + disabled modules (per-user module gating). Split from
 * `UsersController`. Route names preserved (`backend_users_privileges`,
 * `_disabled_modules`).
 */
#[Route('/backend/users', name: 'backend_users')]
#[IsGranted('core.users.manage')]
final class UserPermissionsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly UserManagerInterface $userManager,
        private readonly UserSerializerInterface $userSerializer,
    ) {}

    #[Route('/{id}/privileges', name: '_privileges', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ROLE_DEV')]
    public function privileges(User $user, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !$this->userManager->canActOn($currentUser, $user)) {
            return $this->jsonForbidden();
        }

        // Dev users are not privilege-restricted, no need to manage their list
        if (in_array(UserRoleEnum::Dev->value, $user->getRoles(), true)) {
            return $this->jsonFailure('backend.users.privileges.no_dev_target');
        }

        $privileges = $this->decodeJson($request)['privileges'] ?? [];
        if (!is_array($privileges)) {
            return $this->jsonInvalidInput(['privileges' => 'Invalid format']);
        }

        $this->userManager->updatePrivileges($user, array_values(array_filter($privileges, is_string(...))));

        return $this->jsonSuccess(['user' => $this->userSerializer->serializeWithSubordinates($user)]);
    }

    #[Route('/{id}/disabled-modules', name: '_disabled_modules', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('core.users.modules.manage')]
    public function disabledModules(User $user, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->jsonForbidden();
        }

        $disabledModules = $this->decodeJson($request)['disabledModules'] ?? [];
        if (!is_array($disabledModules)) {
            return $this->jsonInvalidInput(['disabledModules' => 'Invalid format']);
        }

        try {
            $this->userManager->updateDisabledModules(
                $user,
                array_values(array_filter($disabledModules, is_string(...))),
                $currentUser,
            );
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess(['user' => $this->userSerializer->serializeWithSubordinates($user)]);
    }
}
