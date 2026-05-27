<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Service\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Platform\Service\Dto\ServiceInputFactoryInterface;
use Aurora\Module\Platform\Service\Entity\ServiceInterface;
use Aurora\Module\Platform\Service\Manager\ServiceManagerInterface;
use Aurora\Module\Platform\Service\Repository\ServiceRepository;
use Aurora\Module\Platform\Service\Serializer\ServiceSerializerInterface;
use Aurora\Module\Platform\Service\View\ServicesViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/platform/services', name: 'backend_platform_services')]
#[IsGranted('platform.services.manage')]
final class ServicesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly ServiceRepository $serviceRepository,
        private readonly ServiceSerializerInterface $serviceSerializer,
        private readonly ServicesViewBuilder $viewBuilder,
        private readonly ServiceManagerInterface $serviceManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly ServiceInputFactoryInterface $serviceInputFactory,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Platform/backend/services/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('/selectable', name: '_selectable', methods: [HttpMethodEnum::Get->value])]
    public function selectable(): JsonResponse
    {
        $items = array_map(
            static fn (ServiceInterface $service): array => ['value' => (string) $service->getId(), 'label' => $service->getName()],
            $this->serviceRepository->findAllAlphabetical(),
        );

        return $this->jsonSuccess(['items' => $items]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = $this->serviceInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $service = $this->serviceManager->create($input);

        return $this->jsonSuccess(['service' => $this->serviceSerializer->serialize($service)]);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(ServiceInterface $service, Request $request): JsonResponse
    {
        $input = $this->serviceInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->serviceManager->update($service, $input);

        return $this->jsonSuccess(['service' => $this->serviceSerializer->serialize($service)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(ServiceInterface $service): JsonResponse
    {
        $this->serviceManager->delete($service);

        return $this->jsonSuccess();
    }
}
