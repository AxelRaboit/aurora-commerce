<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Dto\WorkflowStepPdfTemplateInputFactoryInterface;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WorkflowStepPdfTemplateInterface;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Manager\WorkflowStepPdfTemplateManagerInterface;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Repository\WorkflowStepPdfTemplateRepository;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Serializer\WorkflowStepPdfTemplateSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/workflow-step-pdf-templates', name: 'backend_welding_workflow_step_pdf_templates')]
#[IsGranted('welding.workflow_templates.view')]
class WorkflowStepPdfTemplatesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly WorkflowStepPdfTemplateRepository $repository,
        protected readonly WorkflowStepPdfTemplateSerializerInterface $serializer,
        protected readonly WorkflowStepPdfTemplateManagerInterface $manager,
        protected readonly WorkflowStepPdfTemplateInputFactoryInterface $inputFactory,
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
    public function update(WorkflowStepPdfTemplateInterface $entry, Request $request): JsonResponse
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
    public function delete(WorkflowStepPdfTemplateInterface $entry): JsonResponse
    {
        $this->manager->delete($entry);

        return $this->jsonSuccess();
    }
}
