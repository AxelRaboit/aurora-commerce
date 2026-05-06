<?php

declare(strict_types=1);

namespace Aurora\Core\Service\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Service\DTO\ServiceInput;
use Aurora\Core\Service\Entity\Service;
use Aurora\Core\Service\Manager\ServiceManager;
use Aurora\Core\Service\Repository\ServiceRepository;
use Aurora\Core\Service\Serializer\ServiceSerializer;
use Aurora\Core\Service\View\ServicesViewBuilder;
use Aurora\Core\Validation\Service\PayloadValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/services', name: 'admin_services')]
#[IsGranted('ROLE_ADMIN')]
final class ServicesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly ServiceRepository $serviceRepository,
        private readonly ServiceSerializer $serviceSerializer,
        private readonly ServicesViewBuilder $viewBuilder,
        private readonly ServiceManager $serviceManager,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Core/admin/services/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('/selectable', name: '_selectable', methods: [HttpMethodEnum::Get->value])]
    public function selectable(): JsonResponse
    {
        $items = array_map(
            static fn (Service $service): array => ['value' => (string) $service->getId(), 'label' => $service->getName()],
            $this->serviceRepository->findAllAlphabetical(),
        );

        return $this->jsonSuccess(['items' => $items]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = ServiceInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $service = $this->serviceManager->create($input);

        return $this->jsonSuccess(['service' => $this->serviceSerializer->serialize($service)]);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Service $service, Request $request): JsonResponse
    {
        $input = ServiceInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->serviceManager->update($service, $input);

        return $this->jsonSuccess(['service' => $this->serviceSerializer->serialize($service)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Service $service): JsonResponse
    {
        $this->serviceManager->delete($service);

        return $this->jsonSuccess();
    }
}
