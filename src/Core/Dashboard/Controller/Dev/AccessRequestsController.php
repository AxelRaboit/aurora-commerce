<?php

declare(strict_types=1);

namespace Aurora\Core\Dashboard\Controller\Dev;

use Aurora\Core\Auth\Contract\AccessRequestManagerInterface;
use Aurora\Core\Auth\Entity\AccessRequest;
use Aurora\Core\Auth\Repository\AccessRequestRepository;
use Aurora\Core\Dashboard\View\AccessRequestsViewBuilder;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\User\Contract\UserManagerInterface;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\Validation\DTO\PaginationRequest;
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
    use JsonResponseTrait;

    public function __construct(
        private readonly AccessRequestRepository $accessRequestRepository,
        private readonly AccessRequestManagerInterface $accessRequestManager,
        private readonly UserManagerInterface $userManager,
        private readonly TranslatorInterface $translator,
        private readonly AccessRequestsViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '')]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        $payload = $this->viewBuilder->accessRequestsPayload($pagination->page, $pagination->search);

        if ('XMLHttpRequest' === $request->headers->get('X-Requested-With')) {
            return $this->json($payload);
        }

        return $this->render('@Core/admin/administration/index.html.twig', $this->viewBuilder->indexView($payload, $pagination->search));
    }

    #[Route('/{id}/approve', name: '_approve', methods: [HttpMethodEnum::Post->value])]
    public function approve(AccessRequest $accessRequest): JsonResponse
    {
        if (!$accessRequest->isPending()) {
            return $this->json(['success' => false], Response::HTTP_CONFLICT);
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

        return $this->jsonSuccess(['message' => $this->translator->trans('admin.access_requests.approved', [
            '{name}' => $accessRequest->getRequesterName() ?? $accessRequest->getRequesterEmail(),
        ])]);
    }

    #[Route('/{id}/reject', name: '_reject', methods: [HttpMethodEnum::Post->value])]
    public function reject(AccessRequest $accessRequest): JsonResponse
    {
        if (!$accessRequest->isPending()) {
            return $this->json(['success' => false], Response::HTTP_CONFLICT);
        }

        $this->accessRequestManager->reject($accessRequest);

        return $this->jsonSuccess(['message' => $this->translator->trans('admin.access_requests.rejected', [
            '{name}' => $accessRequest->getRequesterName() ?? $accessRequest->getRequesterEmail(),
        ])]);
    }

    #[Route('/purge', name: '_purge', methods: [HttpMethodEnum::Post->value])]
    public function purge(): JsonResponse
    {
        $this->accessRequestRepository->deleteProcessed();

        return $this->jsonSuccess(['message' => $this->translator->trans('admin.access_requests.purged')]);
    }
}
