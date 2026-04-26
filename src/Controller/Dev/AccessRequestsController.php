<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Contract\Auth\AccessRequestManagerInterface;
use App\Contract\User\UserManagerInterface;
use App\DTO\PaginationRequest;
use App\Entity\AccessRequest;
use App\Enum\HttpMethodEnum;
use App\Enum\User\UserRoleEnum;
use App\Repository\Auth\AccessRequestRepository;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dev/dashboard/access-requests', name: 'dev_access_requests')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class AccessRequestsController extends AbstractController
{
    public function __construct(
        private readonly AccessRequestRepository $accessRequestRepository,
        private readonly AccessRequestManagerInterface $accessRequestManager,
        private readonly UserManagerInterface $userManager,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('', name: '')]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        $result = $this->accessRequestRepository->findPaginatedAdmin($pagination->page);

        $items = array_map(
            fn (AccessRequest $accessRequest): array => [
                'id' => $accessRequest->getId(),
                'requesterEmail' => $accessRequest->getRequesterEmail(),
                'requesterName' => $accessRequest->getRequesterName(),
                'message' => $accessRequest->getMessage(),
                'status' => $accessRequest->getStatus()->value,
                'expiresAt' => $accessRequest->getExpiresAt()->format(DateTimeInterface::ATOM),
                'createdAt' => $accessRequest->getCreatedAt()->format(DateTimeInterface::ATOM),
            ],
            $result['items'],
        );

        $payload = ['ok' => true, 'items' => $items, 'total' => $result['total'], 'page' => $result['page'], 'totalPages' => $result['totalPages']];

        if ('XMLHttpRequest' === $request->headers->get('X-Requested-With')) {
            return $this->json($payload);
        }

        return $this->render('admin/administration/index.html.twig', [
            'tab' => 'access_requests',
            'accessRequests' => $payload,
        ]);
    }

    #[Route('/{id}/approve', name: '_approve', methods: [HttpMethodEnum::Post->value])]
    public function approve(AccessRequest $accessRequest): JsonResponse
    {
        if (!$accessRequest->isPending()) {
            return $this->json(['ok' => false], Response::HTTP_CONFLICT);
        }

        $generatedPassword = null;
        if (!$this->userManager->isEmailTaken($accessRequest->getRequesterEmail())) {
            $generatedPassword = bin2hex(random_bytes(8));
            $this->userManager->create(
                $accessRequest->getRequesterName() ?? 'Utilisateur',
                $accessRequest->getRequesterEmail(),
                $generatedPassword,
            );
        }

        $this->accessRequestManager->approve($accessRequest, $generatedPassword);

        return $this->json(['ok' => true, 'message' => $this->translator->trans('admin.access_requests.approved', [
            '{name}' => $accessRequest->getRequesterName() ?? $accessRequest->getRequesterEmail(),
        ])]);
    }

    #[Route('/{id}/reject', name: '_reject', methods: [HttpMethodEnum::Post->value])]
    public function reject(AccessRequest $accessRequest): JsonResponse
    {
        if (!$accessRequest->isPending()) {
            return $this->json(['ok' => false], Response::HTTP_CONFLICT);
        }

        $this->accessRequestManager->reject($accessRequest);

        return $this->json(['ok' => true, 'message' => $this->translator->trans('admin.access_requests.rejected', [
            '{name}' => $accessRequest->getRequesterName() ?? $accessRequest->getRequesterEmail(),
        ])]);
    }

    #[Route('/purge', name: '_purge', methods: [HttpMethodEnum::Post->value])]
    public function purge(): JsonResponse
    {
        $this->accessRequestRepository->deleteProcessed();

        return $this->json(['ok' => true, 'message' => $this->translator->trans('admin.access_requests.purged')]);
    }
}
