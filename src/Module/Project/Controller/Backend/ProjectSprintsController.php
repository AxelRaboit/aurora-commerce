<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Project\Dto\ProjectSprintInputFactoryInterface;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectSprint;
use Aurora\Module\Project\Manager\ProjectSprintManager;
use Aurora\Module\Project\Serializer\ProjectSprintSerializer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Project sprints sub-domain — create / update / delete. Split from
 * `ProjectsController`. Route names preserved (`backend_projects_sprint_*`).
 */
#[Route('/backend/projects', name: 'backend_projects')]
#[IsGranted('project.projects.view')]
final class ProjectSprintsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly ProjectSprintManager $sprintManager,
        private readonly ProjectSprintSerializer $sprintSerializer,
        private readonly ProjectSprintInputFactoryInterface $sprintInputFactory,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/{id}/sprints', name: '_sprint_create', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function create(Project $project, Request $request): JsonResponse
    {
        $input = $this->sprintInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $sprint = $this->sprintManager->create($project, $input);

        return $this->jsonSuccess(['sprint' => $this->sprintSerializer->serialize($sprint)]);
    }

    #[Route('/sprints/{sprintId}/update', name: '_sprint_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function update(#[MapEntity(id: 'sprintId')] ProjectSprint $sprint, Request $request): JsonResponse
    {
        $input = $this->sprintInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->sprintManager->update($sprint, $input);

        return $this->jsonSuccess();
    }

    #[Route('/sprints/{sprintId}/delete', name: '_sprint_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function delete(#[MapEntity(id: 'sprintId')] ProjectSprint $sprint): JsonResponse
    {
        $this->sprintManager->delete($sprint);

        return $this->jsonSuccess();
    }
}
