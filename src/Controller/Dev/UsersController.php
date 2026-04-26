<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Contract\User\UserManagerInterface;
use App\Controller\Trait\JsonRequestTrait;
use App\DTO\PaginationRequest;
use App\DTO\User\CreateUserInput;
use App\DTO\User\UpdateUserInput;
use App\Entity\User;
use App\Enum\HttpMethodEnum;
use App\Enum\User\UserRoleEnum;
use App\Repository\User\UserRepository;
use App\Service\PayloadValidator;
use DateTimeInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dev/dashboard/users', name: 'dev_users')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class UsersController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserManagerInterface $userManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        $result = $this->userRepository->findPaginatedForAdmin($pagination->page, $pagination->search);

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $items = array_map(
            fn (User $user): array => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'locale' => $user->getLocale()->value,
                'isDevRole' => in_array(UserRoleEnum::Dev->value, $user->getRoles(), true),
                'createdAt' => $user->getCreatedAt()->format(DateTimeInterface::ATOM),
                'isCurrent' => $user->getId() === $currentUser->getId(),
            ],
            $result['items'],
        );

        return $this->render('admin/administration/index.html.twig', [
            'tab' => 'users',
            'users' => [
                'items' => $items,
                'total' => $result['total'],
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
            ],
            'search' => $pagination->search ?? '',
        ]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $input = CreateUserInput::fromArray($data);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        $user = $this->userManager->create($input->name, $input->email, $input->password);
        $this->userManager->changeLocale($user, $input->locale);
        $this->addFlash('success', $this->translator->trans('admin.users.created'));

        return $this->json(['success' => true, 'id' => $user->getId()]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $input = UpdateUserInput::fromArray($data);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $this->userManager->update($user, $input->name, $input->email);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['email' => $invalidArgumentException->getMessage()]]);
        }

        $this->userManager->changeLocale($user, $input->locale);

        if ('' !== $input->password) {
            $this->userManager->changePassword($user, $input->password);
        }

        $this->addFlash('success', $this->translator->trans('admin.users.updated'));

        return $this->json(['success' => true, 'id' => $user->getId()]);
    }

    #[Route('/{id}/toggle-role', name: '_toggle_role', methods: [HttpMethodEnum::Post->value])]
    public function toggleRole(User $user): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', $this->translator->trans('admin.users.cannot_modify_self'));

            return $this->redirectToRoute('dev_users');
        }

        $isDev = $this->userManager->toggleDevRole($user);
        $this->addFlash(
            'success',
            $this->translator->trans($isDev ? 'admin.users.dev_granted' : 'admin.users.dev_revoked'),
        );

        return $this->redirectToRoute('dev_users');
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(User $user): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', $this->translator->trans('admin.users.cannot_delete_self'));

            return $this->redirectToRoute('dev_users');
        }

        $this->userManager->delete($user);
        $this->addFlash('success', $this->translator->trans('admin.users.deleted'));

        return $this->redirectToRoute('dev_users');
    }
}
