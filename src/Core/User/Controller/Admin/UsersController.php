<?php

declare(strict_types=1);

namespace Aurora\Core\User\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\User\Contract\UserManagerInterface;
use Aurora\Core\User\DTO\UserInput;
use Aurora\Core\User\DTO\UserInviteInput;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Manager\UserHierarchyManager;
use Aurora\Core\User\Manager\UserProfilePhotoManager;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Core\User\Serializer\UserSerializer;
use Aurora\Core\User\View\UsersViewBuilder;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/users', name: 'backend_users')]
#[IsGranted('core.users.manage')]
final class UsersController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserManagerInterface $userManager,
        private readonly UserHierarchyManager $userHierarchyManager,
        private readonly UserSerializer $userSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly UserProfilePhotoManager $userProfilePhotoManager,
        private readonly UsersViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        $currentUser = $this->getUser();

        return $this->render('@Core/admin/users/index.html.twig', $this->viewBuilder->indexView(
            $this->isGranted(UserRoleEnum::Dev->value),
            $currentUser instanceof User ? $currentUser : null,
        ));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination, Request $request): JsonResponse
    {
        $role = mb_trim((string) $request->query->get('role', ''));

        $result = $this->userRepository->findPaginated($pagination->page, 10, $pagination->search, $role ?: null);

        return $this->jsonSuccess([
            'items' => array_map($this->userSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    #[Route('/selectable', name: '_selectable', methods: [HttpMethodEnum::Get->value])]
    public function selectable(): JsonResponse
    {
        $items = array_map(
            static fn (User $user): array => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ],
            $this->userRepository->findAllAdminsAlphabetical(),
        );

        return $this->jsonSuccess(['items' => $items]);
    }

    #[Route('/{id}', name: '_show', methods: [HttpMethodEnum::Get->value])]
    public function show(User $user): JsonResponse
    {
        return $this->jsonSuccess(['user' => $this->userSerializer->serializeWithSubordinates($user)]);
    }

    #[Route('/invite', name: '_invite', methods: [HttpMethodEnum::Post->value])]
    public function invite(Request $request): JsonResponse
    {
        $input = UserInviteInput::fromRequest($request);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        try {
            $user = $this->userManager->invite($input->name, $input->email, $input->role, $input->message);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['role' => $invalidArgumentException->getMessage()], Response::HTTP_OK);
        }

        return $this->jsonSuccess(['user' => $this->userSerializer->serialize($user)]);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(User $user, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !$this->userManager->canActOn($currentUser, $user)) {
            return $this->jsonForbidden();
        }

        $input = UserInput::fromRequest($request);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        try {
            $this->userHierarchyManager->applyManager($user, $input->managerId);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['managerId' => $invalidArgumentException->getMessage()], Response::HTTP_OK);
        }

        try {
            $this->userManager->updateWithRole($user, $input->name, $input->email, $input->role, $input->password);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $field = str_contains($invalidArgumentException->getMessage(), 'email') ? 'email' : 'role';

            return $this->jsonInvalidInput([$field => $invalidArgumentException->getMessage()], Response::HTTP_OK);
        }

        $this->userManager->updateAgencyAndService($user, $input->agencyId, $input->serviceId);

        return $this->jsonSuccess(['user' => $this->userSerializer->serialize($user)]);
    }

    #[Route('/{id}/resend-invitation', name: '_resend_invitation', methods: [HttpMethodEnum::Post->value])]
    public function resendInvitation(User $user): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !$this->userManager->canActOn($currentUser, $user)) {
            return $this->jsonForbidden();
        }

        $this->userManager->resendInvitation($user, null);

        return $this->jsonSuccess(['user' => $this->userSerializer->serialize($user)]);
    }

    #[Route('/{id}/toggle-disabled', name: '_toggle_disabled', methods: [HttpMethodEnum::Post->value])]
    public function toggleDisabled(User $user): JsonResponse
    {
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            return $this->jsonFailure('admin.users.cannot_disable_self');
        }

        if (!$currentUser instanceof User || !$this->userManager->canActOn($currentUser, $user)) {
            return $this->jsonForbidden();
        }

        $this->userManager->toggleDisabled($user);

        return $this->jsonSuccess(['user' => $this->userSerializer->serialize($user)]);
    }

    #[Route('/{id}/photo', name: '_photo_upload', methods: [HttpMethodEnum::Post->value])]
    public function uploadPhoto(User $user, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !$this->userManager->canActOn($currentUser, $user)) {
            return $this->jsonForbidden();
        }

        $file = $request->files->get('photo');
        if (null === $file) {
            return $this->jsonInvalidInput(['photo' => 'admin.users.photo.errors.missing'], Response::HTTP_OK);
        }

        try {
            $this->userProfilePhotoManager->upload($user, $file);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['photo' => $invalidArgumentException->getMessage()], Response::HTTP_OK);
        }

        return $this->jsonSuccess(['user' => $this->userSerializer->serialize($user)]);
    }

    #[Route('/{id}/photo/delete', name: '_photo_delete', methods: [HttpMethodEnum::Post->value])]
    public function deletePhoto(User $user): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !$this->userManager->canActOn($currentUser, $user)) {
            return $this->jsonForbidden();
        }

        $this->userProfilePhotoManager->delete($user);

        return $this->jsonSuccess(['user' => $this->userSerializer->serialize($user)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(User $user): JsonResponse
    {
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            return $this->jsonFailure('admin.users.cannot_delete_self');
        }

        if (!$currentUser instanceof User || !$this->userManager->canActOn($currentUser, $user)) {
            return $this->jsonForbidden();
        }

        $this->userManager->delete($user);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/privileges', name: '_privileges', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ROLE_DEV')]
    public function updatePrivileges(User $user, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !$this->userManager->canActOn($currentUser, $user)) {
            return $this->jsonForbidden();
        }

        // Dev users are not privilege-restricted, no need to manage their list
        if (in_array(UserRoleEnum::Dev->value, $user->getRoles(), true)) {
            return $this->jsonFailure('admin.users.privileges.no_dev_target');
        }

        $privileges = $this->decodeJson($request)['privileges'] ?? [];
        if (!is_array($privileges)) {
            return $this->jsonInvalidInput(['privileges' => 'Invalid format'], Response::HTTP_OK);
        }

        $this->userManager->updatePrivileges($user, array_values(array_filter($privileges, is_string(...))));

        return $this->jsonSuccess(['user' => $this->userSerializer->serializeWithSubordinates($user)]);
    }
}
