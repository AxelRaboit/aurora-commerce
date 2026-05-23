<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Welding\WorkflowTemplate\Dto\WeldingWorkflowTemplateInputFactoryInterface;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplateInterface;
use Aurora\Module\Welding\WorkflowTemplate\Manager\WeldingWorkflowTemplateManagerInterface;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WeldingWorkflowTemplateRepository;
use Aurora\Module\Welding\WorkflowTemplate\Serializer\WeldingWorkflowTemplateSerializerInterface;
use Aurora\Module\Welding\WorkflowTemplate\View\WeldingWorkflowTemplatesViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/workflow-templates', name: 'backend_welding_workflow_templates')]
#[IsGranted('welding.workflow_templates.view')]
class WeldingWorkflowTemplatesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly WeldingWorkflowTemplateRepository $repository,
        protected readonly WeldingWorkflowTemplateSerializerInterface $serializer,
        protected readonly WeldingWorkflowTemplatesViewBuilder $viewBuilder,
        protected readonly WeldingWorkflowTemplateManagerInterface $manager,
        protected readonly WeldingWorkflowTemplateInputFactoryInterface $inputFactory,
        protected readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Welding/backend/workflow_templates/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('/{id}/editor', name: '_editor', requirements: ['id' => '\d+'], methods: [HttpMethodEnum::Get->value])]
    public function editor(WeldingWorkflowTemplateInterface $workflowTemplate): Response
    {
        return $this->render('@Welding/backend/workflow_templates/editor.html.twig', $this->viewBuilder->editorView($workflowTemplate));
    }

    #[Route('/options', name: '_options', methods: [HttpMethodEnum::Get->value])]
    public function options(): JsonResponse
    {
        $items = array_map(
            static fn ($t): array => [
                'value' => (string) $t->getId(),
                'label' => sprintf('%s (v%d)', $t->getTitle(), $t->getVersion()),
                'status' => $t->getStatus()->value,
            ],
            $this->repository->findAllForIndex(),
        );

        return $this->jsonSuccess(['items' => $items]);
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
    public function update(WeldingWorkflowTemplateInterface $workflowTemplate, Request $request): JsonResponse
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
    public function publish(WeldingWorkflowTemplateInterface $workflowTemplate): JsonResponse
    {
        $this->manager->publish($workflowTemplate);

        return $this->jsonSuccess(['workflowTemplate' => $this->serializer->serialize($workflowTemplate)]);
    }

    #[Route('/{id}/archive', name: '_archive', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.edit')]
    public function archive(WeldingWorkflowTemplateInterface $workflowTemplate): JsonResponse
    {
        $this->manager->archive($workflowTemplate);

        return $this->jsonSuccess(['workflowTemplate' => $this->serializer->serialize($workflowTemplate)]);
    }

    #[Route('/{id}/clone', name: '_clone', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.create')]
    public function clone(WeldingWorkflowTemplateInterface $workflowTemplate): JsonResponse
    {
        $clone = $this->manager->cloneAsNewVersion($workflowTemplate);

        return $this->jsonSuccess(['workflowTemplate' => $this->serializer->serialize($clone)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflow_templates.delete')]
    public function delete(WeldingWorkflowTemplateInterface $workflowTemplate): JsonResponse
    {
        $this->manager->delete($workflowTemplate);

        return $this->jsonSuccess();
    }
}
