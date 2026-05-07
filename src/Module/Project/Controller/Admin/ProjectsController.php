<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Project\DTO\ProjectColumnInput;
use Aurora\Module\Project\DTO\ProjectInput;
use Aurora\Module\Project\DTO\ProjectTaskInput;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Manager\ProjectColumnManager;
use Aurora\Module\Project\Manager\ProjectManager;
use Aurora\Module\Project\Manager\ProjectTaskManager;
use Aurora\Module\Project\Repository\ProjectColumnRepository;
use Aurora\Module\Project\Serializer\ProjectColumnSerializer;
use Aurora\Module\Project\Serializer\ProjectSerializer;
use Aurora\Module\Project\Serializer\ProjectTaskSerializer;
use Aurora\Module\Project\View\ProjectsViewBuilder;
use DomainException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/projects', name: 'backend_projects')]
#[IsGranted('project.projects.view')]
final class ProjectsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly ProjectSerializer $projectSerializer,
        private readonly ProjectTaskSerializer $taskSerializer,
        private readonly ProjectColumnSerializer $columnSerializer,
        private readonly ProjectManager $projectManager,
        private readonly ProjectTaskManager $taskManager,
        private readonly ProjectColumnManager $columnManager,
        private readonly ProjectColumnRepository $columnRepository,
        private readonly PayloadValidator $payloadValidator,
        private readonly ProjectsViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Project/admin/projects/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination, Request $request): JsonResponse
    {
        return $this->json($this->viewBuilder->buildListPayload($pagination, $request));
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.projects.create')]
    public function create(Request $request): JsonResponse
    {
        $input = ProjectInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $project = $this->projectManager->create($input);

        return $this->jsonSuccess(['project' => $this->projectSerializer->serialize($project)]);
    }

    #[Route('/{id}', name: '_show', methods: [HttpMethodEnum::Get->value])]
    public function show(Project $project): JsonResponse
    {
        $tasks = array_map($this->taskSerializer->serialize(...), $project->getTasks()->toArray());

        return $this->jsonSuccess([
            'project' => $this->projectSerializer->serialize($project),
            'tasks' => $tasks,
        ]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.projects.edit')]
    public function update(Project $project, Request $request): JsonResponse
    {
        $input = ProjectInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $this->projectManager->update($project, $input);

        return $this->jsonSuccess(['project' => $this->projectSerializer->serialize($project)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.projects.delete')]
    public function delete(Project $project): JsonResponse
    {
        $this->projectManager->delete($project);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/tasks', name: '_task_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function createTask(Project $project, Request $request): JsonResponse
    {
        $input = ProjectTaskInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $task = $this->taskManager->create($project, $input);

        return $this->jsonSuccess(['task' => $this->taskSerializer->serialize($task)]);
    }

    #[Route('/tasks/{taskId}/update', name: '_task_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function updateTask(#[MapEntity(id: 'taskId')] ProjectTask $task, Request $request): JsonResponse
    {
        $input = ProjectTaskInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $this->taskManager->update($task, $input);

        return $this->jsonSuccess(['task' => $this->taskSerializer->serialize($task)]);
    }

    #[Route('/tasks/{taskId}/delete', name: '_task_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function deleteTask(#[MapEntity(id: 'taskId')] ProjectTask $task): JsonResponse
    {
        $this->taskManager->delete($task);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/tasks/reorder', name: '_tasks_reorder', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function reorderTasks(Project $project, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $columnId = isset($data['columnId']) && '' !== (string) $data['columnId'] ? (int) $data['columnId'] : null;
        $orderedIds = array_map(intval(...), (array) ($data['orderedIds'] ?? $data['ids'] ?? []));

        $targetColumn = null !== $columnId ? $this->columnRepository->find($columnId) : null;

        $this->taskManager->reorder($project, $orderedIds, $targetColumn);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/columns', name: '_column_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function createColumn(Project $project, Request $request): JsonResponse
    {
        $input = ProjectColumnInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $column = $this->columnManager->create($project, $input);

        return $this->jsonSuccess(['column' => $this->columnSerializer->serialize($column)]);
    }

    #[Route('/columns/{columnId}/update', name: '_column_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function updateColumn(#[MapEntity(id: 'columnId')] ProjectColumn $column, Request $request): JsonResponse
    {
        $input = ProjectColumnInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $this->columnManager->update($column, $input);

        return $this->jsonSuccess(['column' => $this->columnSerializer->serialize($column)]);
    }

    #[Route('/columns/{columnId}/delete', name: '_column_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function deleteColumn(#[MapEntity(id: 'columnId')] ProjectColumn $column): JsonResponse
    {
        try {
            $this->columnManager->delete($column);
        } catch (DomainException $domainException) {
            return $this->jsonInvalidInput(['_global' => $domainException->getMessage()], Response::HTTP_OK);
        }

        return $this->jsonSuccess();
    }

    #[Route('/{id}/columns/reorder', name: '_columns_reorder', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function reorderColumns(Project $project, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $orderedIds = array_map(intval(...), (array) ($data['orderedIds'] ?? $data['ids'] ?? []));
        $this->columnManager->reorder($project, $orderedIds);

        return $this->jsonSuccess();
    }
}
