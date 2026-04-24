<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Contract\UserManagerInterface;
use App\Controller\Trait\JsonValidationTrait;
use App\DTO\UserInput;
use App\DTO\UserInviteInput;
use App\Entity\User;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use App\Serializer\UserSerializer;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/users', name: 'admin_users')]
#[IsGranted(UserRoleEnum::Admin->value)]
final class UsersController extends AbstractController
{
    use JsonValidationTrait;

    private function currentUserPriority(): int
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return 0;
        }

        foreach (UserRoleEnum::cases() as $role) {
            if (in_array($role->value, $currentUser->getRoles(), true)) {
                return $role->priority();
            }
        }

        return 0;
    }

    private function canActOn(User $target): bool
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        foreach (UserRoleEnum::cases() as $role) {
            if (in_array($role->value, $target->getRoles(), true)) {
                return $this->currentUserPriority() >= $role->priority();
            }
        }

        return true;
    }

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserManagerInterface $userManager,
        private readonly UserSerializer $userSerializer,
        private readonly ValidatorInterface $validator,
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

        return $this->render('admin/users/index.html.twig', [
            'roles' => $roles,
            'isDev' => $this->isGranted(UserRoleEnum::Dev->value),
            'currentUserPriority' => $this->currentUserPriority(),
        ]);
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $search = mb_trim((string) $request->query->get('search', ''));
        $role = mb_trim((string) $request->query->get('role', ''));

        $result = $this->userRepository->findPaginated($page, 10, $search ?: null, $role ?: null);

        $items = array_map(
            $this->userSerializer->serialize(...),
            $result['items'],
        );

        return $this->json([
            'ok' => true,
            'items' => $items,
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    #[Route('/invite', name: '_invite', methods: [HttpMethodEnum::Post->value])]
    public function invite(Request $request): JsonResponse
    {
        $input = UserInviteInput::fromRequest($request);

        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['ok' => false, 'errors' => $this->formatViolations($violations)]);
        }

        if ($this->userManager->isEmailTaken($input->email)) {
            return $this->json(['ok' => false, 'errors' => ['email' => 'admin.users.errors.email_taken']]);
        }

        try {
            $user = $this->userManager->invite($input->name, $input->email, $input->role, $input->message);
        } catch (InvalidArgumentException $error) {
            return $this->json(['ok' => false, 'errors' => ['role' => $error->getMessage()]]);
        }

        return $this->json(['ok' => true, 'user' => $this->userSerializer->serialize($user)]);
    }

    #[Route('/{id}', name: '_update', methods: [HttpMethodEnum::Put->value])]
    public function update(User $user, Request $request): JsonResponse
    {
        if (!$this->canActOn($user)) {
            return $this->json(['ok' => false, 'error' => 'admin.users.cannot_modify_higher_role'], Response::HTTP_FORBIDDEN);
        }

        $input = UserInput::fromRequest($request);

        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['ok' => false, 'errors' => $this->formatViolations($violations)]);
        }

        if ($this->userManager->isEmailTaken($input->email, $user)) {
            return $this->json(['ok' => false, 'errors' => ['email' => 'admin.users.errors.email_taken']]);
        }

        try {
            $this->userManager->updateWithRole($user, $input->name, $input->email, $input->role);
        } catch (InvalidArgumentException $error) {
            return $this->json(['ok' => false, 'errors' => ['role' => $error->getMessage()]]);
        }

        return $this->json(['ok' => true, 'user' => $this->userSerializer->serialize($user)]);
    }

    #[Route('/{id}/resend-invitation', name: '_resend_invitation', methods: [HttpMethodEnum::Post->value])]
    public function resendInvitation(User $user): JsonResponse
    {
        if (!$this->canActOn($user)) {
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

        if (!$this->canActOn($user)) {
            return $this->json(['ok' => false, 'error' => 'admin.users.cannot_modify_higher_role'], Response::HTTP_FORBIDDEN);
        }

        $this->userManager->toggleDisabled($user);

        return $this->json(['ok' => true, 'user' => $this->userSerializer->serialize($user)]);
    }


    #[Route('/{id}', name: '_delete', methods: [HttpMethodEnum::Delete->value])]
    public function delete(User $user): JsonResponse
    {
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            return $this->json(['ok' => false, 'error' => 'admin.users.cannot_delete_self'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->canActOn($user)) {
            return $this->json(['ok' => false, 'error' => 'admin.users.cannot_modify_higher_role'], Response::HTTP_FORBIDDEN);
        }

        $this->userManager->delete($user);

        return $this->json(['ok' => true]);
    }
}
