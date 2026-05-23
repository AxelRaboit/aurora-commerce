<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Welding\WorkflowStepTemplate\Dto\WeldingWorkflowStepTemplateInputFactoryInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Manager\WeldingWorkflowStepTemplateManagerInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WeldingWorkflowStepTemplateRepository;
use Aurora\Module\Welding\WorkflowStepTemplate\Serializer\WeldingWorkflowStepTemplateSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/workflow-step-templates', name: 'backend_welding_workflow_step_templates')]
#[IsGranted('welding.workflow_templates.view')]
class WeldingWorkflowStepTemplatesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly WeldingWorkflowStepTemplateRepository $repository,
        protected readonly WeldingWorkflowStepTemplateSerializerInterface $serializer,
        protected readonly WeldingWorkflowStepTemplateManagerInterface $manager,
        protected readonly WeldingWorkflowStepTemplateInputFactoryInterface $inputFactory,
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

        $step = $this->manager->create($input);

        return $this->jsonSuccess(['step' => $this->serializer->serialize($step)]);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.edit')]
    public function update(WeldingWorkflowStepTemplateInterface $step, Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->manager->update($step, $input);

        return $this->jsonSuccess(['step' => $this->serializer->serialize($step)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.edit')]
    public function delete(WeldingWorkflowStepTemplateInterface $step): JsonResponse
    {
        $this->manager->delete($step);

        return $this->jsonSuccess();
    }

    #[Route('/reorder', name: '_reorder', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.edit')]
    public function reorder(Request $request): JsonResponse
    {
        $payload = $this->decodeJson($request);
        $orderedStepIds = array_values(array_filter(array_map('intval', $payload['orderedStepIds'] ?? []), static fn (int $id): bool => $id > 0));
        if ([] === $orderedStepIds) {
            return $this->jsonInvalidInput(['orderedStepIds' => 'backend.welding.workflow_step_templates.errors.ordered_ids_required']);
        }

        $this->manager->reorder($orderedStepIds);

        return $this->jsonSuccess();
    }
}
