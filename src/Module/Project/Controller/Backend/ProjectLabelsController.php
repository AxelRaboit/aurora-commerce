<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Project\Dto\ProjectLabelInputFactoryInterface;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectLabel;
use Aurora\Module\Project\Manager\ProjectLabelManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Project labels sub-domain — create / update / delete. Split from
 * `ProjectsController`. Route names preserved (`backend_project_projects_label_*`).
 */
#[Route('/backend/project/projects', name: 'backend_project_projects')]
#[IsGranted('project.projects.view')]
final class ProjectLabelsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly ProjectLabelManager $labelManager,
        private readonly ProjectLabelInputFactoryInterface $labelInputFactory,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/{id}/labels', name: '_label_create', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function create(Project $project, Request $request): JsonResponse
    {
        $input = $this->labelInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $label = $this->labelManager->create($project, $input);

        return $this->jsonSuccess(['label' => [
            'id' => $label->getId(),
            'name' => $label->getName(),
            'color' => $label->getColor(),
        ]]);
    }

    #[Route('/labels/{labelId}/update', name: '_label_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function update(#[MapEntity(id: 'labelId')] ProjectLabel $label, Request $request): JsonResponse
    {
        $input = $this->labelInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->labelManager->update($label, $input);

        return $this->jsonSuccess();
    }

    #[Route('/labels/{labelId}/delete', name: '_label_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function delete(#[MapEntity(id: 'labelId')] ProjectLabel $label): JsonResponse
    {
        $this->labelManager->delete($label);

        return $this->jsonSuccess();
    }
}
