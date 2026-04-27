<?php

declare(strict_types=1);

namespace Aurora\Core\User\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\User\Contract\UserManagerInterface;
use Aurora\Core\User\DTO\UserInput;
use Aurora\Core\User\DTO\UserInviteInput;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Core\User\Serializer\UserSerializer;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_users')]
#[IsGranted('core.users.manage')]
final class UsersController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserManagerInterface $userManager,
        private readonly UserSerializer $userSerializer,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        $selectableRoles = $this->isGranted(UserRoleEnum::Dev->value)
            ? [UserRoleEnum::Dev, ...UserRoleEnum::selectableForAdmin()]
            : UserRoleEnum::selectableForAdmin();

        $roles = array_map(
            static fn (UserRoleEnum $role): array => ['value' => $role->value, 'label' => $role->label()],
            $selectableRoles,
        );

        $currentUser = $this->getUser();
        $currentUserPriority = $currentUser instanceof User
            ? UserRoleEnum::highestPriorityForRoles($currentUser->getRoles())
            : 0;

        return $this->render('@Core/admin/users/index.html.twig', [
            'roles' => $roles,
            'isDev' => $this->isGranted(UserRoleEnum::Dev->value),
            'currentUserPriority' => $currentUserPriority,
        ]);
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination, Request $request): JsonResponse
    {
        $role = mb_trim((string) $request->query->get('role', ''));

        $result = $this->userRepository->findPaginated($pagination->page, 10, $pagination->search, $role ?: null);

        return $this->json([
            'ok' => true,
            'items' => array_map($this->userSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    #[Route('/invite', name: '_invite', methods: [HttpMethodEnum::Post->value])]
    public function invite(Request $request): JsonResponse
    {
        $input = UserInviteInput::fromRequest($request);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['ok' => false, 'errors' => $errors]);
        }

        try {
            $user = $this->userManager->invite($input->name, $input->email, $input->role, $input->message);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['ok' => false, 'errors' => ['role' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['ok' => true, 'user' => $this->userSerializer->serialize($user)]);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(User $user, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !$this->userManager->canActOn($currentUser, $user)) {
            return $this->json(['ok' => false, 'error' => 'admin.users.cannot_modify_higher_role'], Response::HTTP_FORBIDDEN);
        }

        $input = UserInput::fromRequest($request);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['ok' => false, 'errors' => $errors]);
        }

        try {
            $this->userManager->updateWithRole($user, $input->name, $input->email, $input->role, $input->password);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $field = str_contains($invalidArgumentException->getMessage(), 'email') ? 'email' : 'role';

            return $this->json(['ok' => false, 'errors' => [$field => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['ok' => true, 'user' => $this->userSerializer->serialize($user)]);
    }

    #[Route('/{id}/resend-invitation', name: '_resend_invitation', methods: [HttpMethodEnum::Post->value])]
    public function resendInvitation(User $user): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !$this->userManager->canActOn($currentUser, $user)) {
            return $this->json(['ok' => false, 'error' => 'admin.users.cannot_modify_higher_role'], Response::HTTP_FORBIDDEN);
        }

        $this->userManager->resendInvitation($user, null);

        return $this->json(['ok' => true, 'user' => $this->userSerializer->serialize($user)]);
    }

    #[Route('/{id}/toggle-disabled', name: '_toggle_disabled', methods: [HttpMethodEnum::Post->value])]
    public function toggleDisabled(User $user): JsonResponse
    {
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            return $this->json(['ok' => false, 'error' => 'admin.users.cannot_disable_self'], Response::HTTP_BAD_REQUEST);
        }

        if (!$currentUser instanceof User || !$this->userManager->canActOn($currentUser, $user)) {
            return $this->json(['ok' => false, 'error' => 'admin.users.cannot_modify_higher_role'], Response::HTTP_FORBIDDEN);
        }

        $this->userManager->toggleDisabled($user);

        return $this->json(['ok' => true, 'user' => $this->userSerializer->serialize($user)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(User $user): JsonResponse
    {
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            return $this->json(['ok' => false, 'error' => 'admin.users.cannot_delete_self'], Response::HTTP_BAD_REQUEST);
        }

        if (!$currentUser instanceof User || !$this->userManager->canActOn($currentUser, $user)) {
            return $this->json(['ok' => false, 'error' => 'admin.users.cannot_modify_higher_role'], Response::HTTP_FORBIDDEN);
        }

        $this->userManager->delete($user);

        return $this->json(['ok' => true]);
    }
}
