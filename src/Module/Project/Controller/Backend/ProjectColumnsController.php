<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Project\Dto\ProjectColumnInputFactoryInterface;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Manager\ProjectColumnManager;
use Aurora\Module\Project\Serializer\ProjectColumnSerializer;
use DomainException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Project columns (kanban board) sub-domain — create / update / delete /
 * reorder. Split from `ProjectsController`. All route names preserved
 * (`backend_projects_column_*`, `backend_projects_columns_reorder`).
 */
#[Route('/backend/projects', name: 'backend_projects')]
#[IsGranted('project.projects.view')]
final class ProjectColumnsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly ProjectColumnManager $columnManager,
        private readonly ProjectColumnSerializer $columnSerializer,
        private readonly ProjectColumnInputFactoryInterface $columnInputFactory,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/{id}/columns', name: '_column_create', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function create(Project $project, Request $request): JsonResponse
    {
        $input = $this->columnInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $column = $this->columnManager->create($project, $input);

        return $this->jsonSuccess(['column' => $this->columnSerializer->serialize($column)]);
    }

    #[Route('/columns/{columnId}/update', name: '_column_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function update(#[MapEntity(id: 'columnId')] ProjectColumn $column, Request $request): JsonResponse
    {
        $input = $this->columnInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->columnManager->update($column, $input);

        return $this->jsonSuccess(['column' => $this->columnSerializer->serialize($column)]);
    }

    #[Route('/columns/{columnId}/delete', name: '_column_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function delete(#[MapEntity(id: 'columnId')] ProjectColumn $column): JsonResponse
    {
        try {
            $this->columnManager->delete($column);
        } catch (DomainException $domainException) {
            return $this->jsonInvalidInput(['_global' => $domainException->getMessage()]);
        }

        return $this->jsonSuccess();
    }

    #[Route('/{id}/columns/reorder', name: '_columns_reorder', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function reorder(Project $project, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $orderedIds = array_map(intval(...), (array) ($data['orderedIds'] ?? $data['ids'] ?? []));
        $this->columnManager->reorder($project, $orderedIds);

        return $this->jsonSuccess();
    }
}
