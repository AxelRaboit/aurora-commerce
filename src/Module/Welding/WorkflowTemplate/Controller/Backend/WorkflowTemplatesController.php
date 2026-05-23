<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Welding\WorkflowTemplate\Dto\WorkflowTemplateInputFactoryInterface;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WorkflowTemplateInterface;
use Aurora\Module\Welding\WorkflowTemplate\Manager\WorkflowTemplateManagerInterface;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WorkflowTemplateRepository;
use Aurora\Module\Welding\WorkflowTemplate\Serializer\WorkflowTemplateSerializerInterface;
use Aurora\Module\Welding\WorkflowTemplate\View\WorkflowTemplatesViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/workflow-templates', name: 'backend_welding_workflow_templates')]
#[IsGranted('welding.workflow_templates.view')]
class WorkflowTemplatesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly WorkflowTemplateRepository $repository,
        protected readonly WorkflowTemplateSerializerInterface $serializer,
        protected readonly WorkflowTemplatesViewBuilder $viewBuilder,
        protected readonly WorkflowTemplateManagerInterface $manager,
        protected readonly WorkflowTemplateInputFactoryInterface $inputFactory,
        protected readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Welding/backend/workflow_templates/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.create')]
    public function create(Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $workflowTemplate = $this->manager->create($input);

        return $this->jsonSuccess(['workflowTemplate' => $this->serializer->serialize($workflowTemplate)]);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.edit')]
    public function update(WorkflowTemplateInterface $workflowTemplate, Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->manager->update($workflowTemplate, $input);

        return $this->jsonSuccess(['workflowTemplate' => $this->serializer->serialize($workflowTemplate)]);
    }

    #[Route('/{id}/publish', name: '_publish', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.edit')]
    public function publish(WorkflowTemplateInterface $workflowTemplate): JsonResponse
    {
        $this->manager->publish($workflowTemplate);

        return $this->jsonSuccess(['workflowTemplate' => $this->serializer->serialize($workflowTemplate)]);
    }

    #[Route('/{id}/archive', name: '_archive', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.edit')]
    public function archive(WorkflowTemplateInterface $workflowTemplate): JsonResponse
    {
        $this->manager->archive($workflowTemplate);

        return $this->jsonSuccess(['workflowTemplate' => $this->serializer->serialize($workflowTemplate)]);
    }

    #[Route('/{id}/clone', name: '_clone', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.create')]
    public function clone(WorkflowTemplateInterface $workflowTemplate): JsonResponse
    {
        $clone = $this->manager->cloneAsNewVersion($workflowTemplate);

        return $this->jsonSuccess(['workflowTemplate' => $this->serializer->serialize($clone)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.delete')]
    public function delete(WorkflowTemplateInterface $workflowTemplate): JsonResponse
    {
        $this->manager->delete($workflowTemplate);

        return $this->jsonSuccess();
    }
}
