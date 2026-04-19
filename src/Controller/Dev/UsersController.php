<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Contract\UserManagerInterface;
use App\Controller\Trait\JsonValidationTrait;
use App\DTO\CreateUserInput;
use App\Entity\User;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dev/users', name: 'dev_users')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class UsersController extends AbstractController
{
    use JsonValidationTrait;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserManagerInterface $userManager,
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query->get('search', ''));
        $page = max(1, (int) $request->query->get('page', '1'));
        $result = $this->userRepository->findPaginatedForAdmin($page, $search ?: null);

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $items = array_map(
            fn (User $user): array => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
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
            'search' => $search,
        ]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $input = CreateUserInput::fromArray($data);

        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['success' => false, 'errors' => $this->formatViolations($violations)]);
        }

        $user = $this->userManager->create($input->name, $input->email, $input->password);
        $this->addFlash('success', $this->translator->trans('admin.users.created'));

        return $this->json(['success' => true, 'id' => $user->getId()]);
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
