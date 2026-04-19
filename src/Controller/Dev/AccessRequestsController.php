<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Contract\AccessRequestManagerInterface;
use App\Contract\UserManagerInterface;
use App\Entity\AccessRequest;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\AccessRequestRepository;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/dev/access-requests', name: 'dev_access_requests')]
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
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $result = $this->accessRequestRepository->findPaginatedAdmin($page);

        return $this->render('admin/administration/index.html.twig', [
            'tab' => 'access_requests',
            'accessRequests' => [
                ...$result,
                'items' => array_map(
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
                ),
            ],
        ]);
    }

    #[Route('/{id}/approve', name: '_approve', methods: [HttpMethodEnum::Post->value])]
    public function approve(AccessRequest $accessRequest): Response
    {
        if ($accessRequest->isPending()) {
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
            $this->addFlash('success', $this->translator->trans('admin.access_requests.approved', [
                '{name}' => $accessRequest->getRequesterName() ?? $accessRequest->getRequesterEmail(),
            ]));
        }

        return $this->redirectToRoute('dev_access_requests');
    }

    #[Route('/{id}/reject', name: '_reject', methods: [HttpMethodEnum::Post->value])]
    public function reject(AccessRequest $accessRequest): Response
    {
        if ($accessRequest->isPending()) {
            $this->accessRequestManager->reject($accessRequest);
            $this->addFlash('success', $this->translator->trans('admin.access_requests.rejected', [
                '{name}' => $accessRequest->getRequesterName() ?? $accessRequest->getRequesterEmail(),
            ]));
        }

        return $this->redirectToRoute('dev_access_requests');
    }

    #[Route('/purge', name: '_purge', methods: [HttpMethodEnum::Post->value])]
    public function purge(): Response
    {
        $this->accessRequestRepository->deleteProcessed();
        $this->addFlash('success', $this->translator->trans('admin.access_requests.purged'));

        return $this->redirectToRoute('dev_access_requests');
    }
}
