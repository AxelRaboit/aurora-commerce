<?php

declare(strict_types=1);

namespace App\Core\Audit\Controller\Dev;

use App\Core\Audit\Repository\AuditLogRepository;
use App\Core\Audit\Serializer\AuditLogSerializer;
use App\Core\Enum\HttpMethodEnum;
use App\Core\User\Enum\UserRoleEnum;
use App\Core\Validation\DTO\PaginationRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev/dashboard/audit', name: 'dev_audit')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class AuditController extends AbstractController
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository,
        private readonly AuditLogSerializer $auditLogSerializer,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        $module = $request->query->get('module') ?: null;
        $result = $this->auditLogRepository->findPaginated($pagination->page, 50, $module);

        $payload = [
            'ok' => true,
            'items' => array_map($this->auditLogSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'modules' => $this->auditLogRepository->findDistinctModules(),
            'module' => $module,
        ];

        if ('XMLHttpRequest' === $request->headers->get('X-Requested-With')) {
            return $this->json($payload);
        }

        return $this->render('@Core/admin/administration/index.html.twig', [
            'tab' => 'audit',
            'audit' => $payload,
            'search' => '',
        ]);
    }
}
