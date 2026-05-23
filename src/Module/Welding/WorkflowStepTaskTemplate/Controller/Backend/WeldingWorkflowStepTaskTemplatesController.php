<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTaskTemplate\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Dto\WeldingWorkflowStepTaskTemplateInputFactoryInterface;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Manager\WeldingWorkflowStepTaskTemplateManagerInterface;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Repository\WeldingWorkflowStepTaskTemplateRepository;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Serializer\WeldingWorkflowStepTaskTemplateSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/workflow-step-task-templates', name: 'backend_welding_workflow_step_task_templates')]
#[IsGranted('welding.workflow_templates.view')]
class WeldingWorkflowStepTaskTemplatesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly WeldingWorkflowStepTaskTemplateRepository $repository,
        protected readonly WeldingWorkflowStepTaskTemplateSerializerInterface $serializer,
        protected readonly WeldingWorkflowStepTaskTemplateManagerInterface $manager,
        protected readonly WeldingWorkflowStepTaskTemplateInputFactoryInterface $inputFactory,
        protected readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.edit')]
    public function create(Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $entry = $this->manager->create($input);

        return $this->jsonSuccess(['entry' => $this->serializer->serialize($entry)]);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.edit')]
    public function update(WeldingWorkflowStepTaskTemplateInterface $entry, Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->manager->update($entry, $input);

        return $this->jsonSuccess(['entry' => $this->serializer->serialize($entry)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.edit')]
    public function delete(WeldingWorkflowStepTaskTemplateInterface $entry): JsonResponse
    {
        $this->manager->delete($entry);

        return $this->jsonSuccess();
    }

    #[Route('/{stepId<\d+>}/reorder', name: '_reorder', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.edit')]
    public function reorder(int $stepId, Request $request): JsonResponse
    {
        $payload = $this->decodeJson($request);
        $orderedIds = array_values(array_map(intval(...), $payload['orderedIds'] ?? []));

        if ([] === $orderedIds) {
            return $this->jsonInvalidInput(['orderedIds' => 'welding.workflow_step_tasks.errors.ordered_ids_required']);
        }

        $this->manager->reorder($stepId, $orderedIds);

        return $this->jsonSuccess();
    }
}
