<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\Controller\Backend;

use Aurora\Core\Agency\Dto\AgencyInputFactoryInterface;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Agency\Manager\AgencyManagerInterface;
use Aurora\Core\Agency\Repository\AgencyRepository;
use Aurora\Core\Agency\Serializer\AgencySerializerInterface;
use Aurora\Core\Agency\View\AgenciesViewBuilder;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/agencies', name: 'backend_agencies')]
#[IsGranted('ROLE_ADMIN')]
class AgenciesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly AgencyRepository $agencyRepository,
        protected readonly AgencySerializerInterface $agencySerializer,
        protected readonly AgenciesViewBuilder $viewBuilder,
        protected readonly AgencyManagerInterface $agencyManager,
        protected readonly AgencyInputFactoryInterface $agencyInputFactory,
        protected readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Core/backend/agencies/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('/selectable', name: '_selectable', methods: [HttpMethodEnum::Get->value])]
    public function selectable(): JsonResponse
    {
        $items = array_map(
            static fn (AgencyInterface $agency): array => ['value' => (string) $agency->getId(), 'label' => $agency->getName()],
            $this->agencyRepository->findAllAlphabetical(),
        );

        return $this->jsonSuccess(['items' => $items]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = $this->agencyInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $agency = $this->agencyManager->create($input);

        return $this->jsonSuccess(['agency' => $this->agencySerializer->serialize($agency)]);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(AgencyInterface $agency, Request $request): JsonResponse
    {
        $input = $this->agencyInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->agencyManager->update($agency, $input);

        return $this->jsonSuccess(['agency' => $this->agencySerializer->serialize($agency)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(AgencyInterface $agency): JsonResponse
    {
        $this->agencyManager->delete($agency);

        return $this->jsonSuccess();
    }
}
