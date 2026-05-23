<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Welding\WorkflowStepTemplate\Dto\WorkflowStepTemplateInputFactoryInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Manager\WorkflowStepTemplateManagerInterface;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WorkflowStepTemplateRepository;
use Aurora\Module\Welding\WorkflowStepTemplate\Serializer\WorkflowStepTemplateSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/workflow-step-templates', name: 'backend_welding_workflow_step_templates')]
#[IsGranted('welding.workflow_templates.view')]
class WorkflowStepTemplatesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly WorkflowStepTemplateRepository $repository,
        protected readonly WorkflowStepTemplateSerializerInterface $serializer,
        protected readonly WorkflowStepTemplateManagerInterface $manager,
        protected readonly WorkflowStepTemplateInputFactoryInterface $inputFactory,
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
    public function update(WorkflowStepTemplateInterface $step, Request $request): JsonResponse
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
    public function delete(WorkflowStepTemplateInterface $step): JsonResponse
    {
        $this->manager->delete($step);

        return $this->jsonSuccess();
    }
}
