<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Planning\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Planning\Planning\Dto\PlanningInputFactoryInterface;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use Aurora\Module\Planning\Planning\Manager\PlanningManagerInterface;
use Aurora\Module\Planning\Planning\Serializer\PlanningSerializerInterface;
use Aurora\Module\Planning\Planning\View\PlanningsViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/planning/plannings', name: 'backend_planning_plannings')]
#[IsGranted('planning.plannings.view')]
class PlanningsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly PlanningSerializerInterface $planningSerializer,
        protected readonly PlanningsViewBuilder $viewBuilder,
        protected readonly PlanningManagerInterface $planningManager,
        protected readonly PlanningInputFactoryInterface $planningInputFactory,
        protected readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Planning/backend/plannings/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('planning.plannings.create')]
    public function create(Request $request): JsonResponse
    {
        $input = $this->planningInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $planning = $this->planningManager->create($input);

        return $this->jsonSuccess(['planning' => $this->planningSerializer->serialize($planning)]);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('planning.plannings.edit')]
    public function update(PlanningInterface $planning, Request $request): JsonResponse
    {
        $input = $this->planningInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->planningManager->update($planning, $input);

        return $this->jsonSuccess(['planning' => $this->planningSerializer->serialize($planning)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('planning.plannings.delete')]
    public function delete(PlanningInterface $planning): JsonResponse
    {
        $this->planningManager->delete($planning);

        return $this->jsonSuccess();
    }
}
