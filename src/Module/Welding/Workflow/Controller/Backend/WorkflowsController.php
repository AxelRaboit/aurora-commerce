<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Support\Str;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Welding\Workflow\Dto\WorkflowInputFactoryInterface;
use Aurora\Module\Welding\Workflow\Entity\WorkflowInterface;
use Aurora\Module\Welding\Workflow\Manager\WorkflowManagerInterface;
use Aurora\Module\Welding\Workflow\Repository\WorkflowRepository;
use Aurora\Module\Welding\Workflow\Serializer\WorkflowSerializerInterface;
use Aurora\Module\Welding\Workflow\View\WorkflowsViewBuilder;
use Aurora\Module\Welding\WorkflowStep\Serializer\WorkflowStepSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/workflows', name: 'backend_welding_workflows')]
#[IsGranted('welding.workflows.view')]
class WorkflowsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly WorkflowRepository $repository,
        protected readonly WorkflowSerializerInterface $serializer,
        protected readonly WorkflowStepSerializerInterface $stepSerializer,
        protected readonly WorkflowsViewBuilder $viewBuilder,
        protected readonly WorkflowManagerInterface $manager,
        protected readonly WorkflowInputFactoryInterface $inputFactory,
        protected readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Welding/backend/workflows/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('/{id}', name: '_show', requirements: ['id' => '\d+'], methods: [HttpMethodEnum::Get->value])]
    public function show(WorkflowInterface $workflow): JsonResponse
    {
        $steps = array_map(
            $this->stepSerializer->serialize(...),
            $workflow->getSteps()->toArray(),
        );

        return $this->jsonSuccess([
            'workflow' => $this->serializer->serialize($workflow),
            'steps' => $steps,
        ]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflows.start')]
    public function create(Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $workflow = $this->manager->create($input);

        return $this->jsonSuccess(['workflow' => $this->serializer->serialize($workflow)]);
    }

    #[Route('/{id}/start', name: '_start', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflows.start')]
    public function start(WorkflowInterface $workflow): JsonResponse
    {
        $this->manager->start($workflow);

        return $this->jsonSuccess(['workflow' => $this->serializer->serialize($workflow)]);
    }

    #[Route('/{id}/reject', name: '_reject', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflows.archive')]
    public function reject(WorkflowInterface $workflow, Request $request): JsonResponse
    {
        $payload = $this->decodeJson($request);
        $reason = Str::trimFromArray($payload, 'reason');
        if ('' === $reason) {
            return $this->jsonInvalidInput(['reason' => 'backend.welding.workflows.errors.reason_required']);
        }

        $this->manager->reject($workflow, $reason);

        return $this->jsonSuccess(['workflow' => $this->serializer->serialize($workflow)]);
    }

    #[Route('/{id}/archive', name: '_archive', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflows.archive')]
    public function archive(WorkflowInterface $workflow): JsonResponse
    {
        $this->manager->archive($workflow);

        return $this->jsonSuccess(['workflow' => $this->serializer->serialize($workflow)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflows.archive')]
    public function delete(WorkflowInterface $workflow): JsonResponse
    {
        $this->manager->delete($workflow);

        return $this->jsonSuccess();
    }
}
