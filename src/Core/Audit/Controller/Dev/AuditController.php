<?php

declare(strict_types=1);

namespace Aurora\Core\Audit\Controller\Dev;

use Aurora\Core\Audit\View\AuditViewBuilder;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev/dashboard/audit', name: 'dev_audit')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class AuditController extends AbstractController
{
    public function __construct(private readonly AuditViewBuilder $viewBuilder) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        $module = $request->query->get('module') ?: null;
        $payload = $this->viewBuilder->auditPayload($pagination->page, $module);

        if ('XMLHttpRequest' === $request->headers->get('X-Requested-With')) {
            return $this->json($payload);
        }

        return $this->render('@Core/backend/dev/index.html.twig', $this->viewBuilder->indexView($payload));
    }
}
