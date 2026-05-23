<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Support\Str;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Welding\Workflow\Dto\WeldingWorkflowInputFactoryInterface;
use Aurora\Module\Welding\Workflow\Entity\WeldingWorkflowInterface;
use Aurora\Module\Welding\Workflow\Manager\WeldingWorkflowManagerInterface;
use Aurora\Module\Welding\Workflow\Repository\WeldingWorkflowRepository;
use Aurora\Module\Welding\Workflow\Serializer\WeldingWorkflowSerializerInterface;
use Aurora\Module\Welding\Workflow\View\WeldingWorkflowRunnerViewBuilder;
use Aurora\Module\Welding\WorkflowStep\Serializer\WeldingWorkflowStepSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/workflows', name: 'backend_welding_workflows')]
#[IsGranted('welding.workflows.view')]
class WeldingWorkflowsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly WeldingWorkflowRepository $repository,
        protected readonly WeldingWorkflowSerializerInterface $serializer,
        protected readonly WeldingWorkflowStepSerializerInterface $stepSerializer,
        protected readonly WeldingWorkflowRunnerViewBuilder $runnerViewBuilder,
        protected readonly WeldingWorkflowManagerInterface $manager,
        protected readonly WeldingWorkflowInputFactoryInterface $inputFactory,
        protected readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Welding/backend/workflows/index.html.twig');
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(100, (int) $request->query->get('limit', 20)));
        $search = (string) $request->query->get('search', '');
        $status = (string) $request->query->get('status', '');

        $result = $this->repository->findPaginated(
            page: $page,
            limit: $limit,
            search: '' !== $search ? $search : null,
            status: '' !== $status ? $status : null,
        );

        return $this->jsonSuccess([
            'items' => array_map($this->serializer->serialize(...), $result['items']),
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total'],
        ]);
    }

    #[Route('/{id}/runner', name: '_runner', requirements: ['id' => '\d+'], methods: [HttpMethodEnum::Get->value])]
    public function runner(WeldingWorkflowInterface $workflow): Response
    {
        return $this->render('@Welding/backend/workflows/runner.html.twig', $this->runnerViewBuilder->runnerView($workflow));
    }

    #[Route('/{id}/state', name: '_state', requirements: ['id' => '\d+'], methods: [HttpMethodEnum::Get->value])]
    public function state(WeldingWorkflowInterface $workflow): JsonResponse
    {
        return $this->jsonSuccess($this->runnerViewBuilder->runnerView($workflow));
    }

    #[Route('/{id}', name: '_show', requirements: ['id' => '\d+'], methods: [HttpMethodEnum::Get->value])]
    public function show(WeldingWorkflowInterface $workflow): JsonResponse
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
    public function start(WeldingWorkflowInterface $workflow): JsonResponse
    {
        $this->manager->start($workflow);

        return $this->jsonSuccess(['workflow' => $this->serializer->serialize($workflow)]);
    }

    #[Route('/{id}/reject', name: '_reject', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflows.archive')]
    public function reject(WeldingWorkflowInterface $workflow, Request $request): JsonResponse
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
    public function archive(WeldingWorkflowInterface $workflow): JsonResponse
    {
        $this->manager->archive($workflow);

        return $this->jsonSuccess(['workflow' => $this->serializer->serialize($workflow)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflows.archive')]
    public function delete(WeldingWorkflowInterface $workflow): JsonResponse
    {
        $this->manager->delete($workflow);

        return $this->jsonSuccess();
    }
}
