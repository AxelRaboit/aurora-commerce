<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTask\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\WorkflowStepTask\Entity\WeldingWorkflowStepTaskInterface;
use Aurora\Module\Welding\WorkflowStepTask\Manager\WeldingWorkflowStepTaskManagerInterface;
use Aurora\Module\Welding\WorkflowStepTask\Serializer\WeldingWorkflowStepTaskSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/workflow-step-tasks', name: 'backend_welding_workflow_step_tasks')]
#[IsGranted('welding.workflows.fill')]
class WeldingWorkflowStepTasksController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly WeldingWorkflowStepTaskManagerInterface $manager,
        protected readonly WeldingWorkflowStepTaskSerializerInterface $serializer,
    ) {}

    #[Route('/{id}/toggle', name: '_toggle', methods: [HttpMethodEnum::Post->value])]
    public function toggle(WeldingWorkflowStepTaskInterface $task, Request $request): JsonResponse
    {
        $payload = $this->decodeJson($request);
        $done = (bool) ($payload['done'] ?? !$task->getDone());

        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $this->manager->setDone($task, $done, $user);

        return $this->jsonSuccess(['task' => $this->serializer->serialize($task)]);
    }
}
