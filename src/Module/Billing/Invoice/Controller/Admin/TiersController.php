<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Billing\Invoice\Contract\TiersManagerInterface;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Billing\Invoice\Serializer\TiersSerializer;
use Aurora\Module\Billing\Invoice\View\TiersViewBuilder;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use function is_array;

#[Route('/backend/billing/tiers', name: 'backend_billing_tiers')]
#[IsGranted('billing.tiers.view')]
final class TiersController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly TiersViewBuilder $viewBuilder,
        private readonly TiersSerializer $tiersSerializer,
        private readonly TiersManagerInterface $tiersManager,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        $pagination = PaginationRequest::fromRequest($request);
        $type = TiersTypeEnum::tryFrom($request->query->getString('type', ''));

        return $this->render('@Billing/admin/tiers/index.html.twig', $this->viewBuilder->indexView($pagination, $type));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(Request $request): JsonResponse
    {
        $pagination = PaginationRequest::fromRequest($request);
        $type = TiersTypeEnum::tryFrom($request->query->getString('type', ''));

        return $this->json($this->viewBuilder->buildListPayload($pagination, $type));
    }

    #[Route('/{id}', name: '_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function show(Tiers $tiers): Response
    {
        return $this->render('@Billing/admin/tiers/show.html.twig', $this->viewBuilder->showView($tiers));
    }

    #[Route('/{id}/update', name: '_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('billing.tiers.manage')]
    public function update(Tiers $tiers, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !isset($payload['field'])) {
            return $this->jsonInvalidInput(['field' => 'backend.billing.tiers.update.fieldRequired']);
        }

        try {
            $this->tiersManager->updateField($tiers, (string) $payload['field'], $payload['value'] ?? null);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput([(string) $payload['field'] => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['tiers' => $this->tiersSerializer->serialize($tiers)]);
    }

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('billing.tiers.manage')]
    public function delete(Tiers $tiers): JsonResponse
    {
        try {
            $this->tiersManager->delete($tiers);
        } catch (Throwable $throwable) {
            return $this->jsonFailure('backend.billing.tiers.deleteError', extra: ['detail' => $throwable->getMessage()]);
        }

        return $this->jsonSuccess();
    }
}
