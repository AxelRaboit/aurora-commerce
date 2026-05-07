<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Controller\Admin;

use Aurora\Core\Audit\Repository\AuditLogRepository;
use Aurora\Core\Audit\Serializer\AuditLogSerializer;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\User\Entity\User;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Project\DTO\ProjectColumnInput;
use Aurora\Module\Project\DTO\ProjectInput;
use Aurora\Module\Project\DTO\ProjectLabelInput;
use Aurora\Module\Project\DTO\ProjectSprintInput;
use Aurora\Module\Project\DTO\ProjectTaskCommentInput;
use Aurora\Module\Project\DTO\ProjectTaskInput;
use Aurora\Module\Project\DTO\ProjectTaskItemsInput;
use Aurora\Module\Project\DTO\ProjectTaskTimeEntryInput;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectLabel;
use Aurora\Module\Project\Entity\ProjectSavedView;
use Aurora\Module\Project\Entity\ProjectSprint;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskComment;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntry;
use Aurora\Module\Project\Manager\ProjectColumnManager;
use Aurora\Module\Project\Manager\ProjectInvoiceManager;
use Aurora\Module\Project\Manager\ProjectLabelManager;
use Aurora\Module\Project\Manager\ProjectManager;
use Aurora\Module\Project\Manager\ProjectSavedViewManager;
use Aurora\Module\Project\Manager\ProjectSprintManager;
use Aurora\Module\Project\Manager\ProjectTaskAttachmentManager;
use Aurora\Module\Project\Manager\ProjectTaskCommentManager;
use Aurora\Module\Project\Manager\ProjectTaskItemManager;
use Aurora\Module\Project\Manager\ProjectTaskManager;
use Aurora\Module\Project\Manager\ProjectTaskTimeEntryManager;
use Aurora\Module\Project\Repository\ProjectColumnRepository;
use Aurora\Module\Project\Repository\ProjectSavedViewRepository;
use Aurora\Module\Project\Serializer\ProjectColumnSerializer;
use Aurora\Module\Project\Serializer\ProjectSerializer;
use Aurora\Module\Project\Serializer\ProjectSprintSerializer;
use Aurora\Module\Project\Serializer\ProjectTaskCommentSerializer;
use Aurora\Module\Project\Serializer\ProjectTaskSerializer;
use Aurora\Module\Project\Serializer\ProjectTaskTimeEntrySerializer;
use Aurora\Module\Project\View\ProjectsViewBuilder;
use DomainException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        private readonly ProjectLabelManager $labelManager,
        private readonly ProjectTaskItemManager $taskItemManager,
        private readonly ProjectTaskTimeEntryManager $timeEntryManager,
        private readonly ProjectTaskTimeEntrySerializer $timeEntrySerializer,
        private readonly ProjectTaskCommentManager $commentManager,
        private readonly ProjectTaskCommentSerializer $commentSerializer,
        private readonly ProjectTaskAttachmentManager $attachmentManager,
        private readonly ProjectSprintManager $sprintManager,
        private readonly ProjectSprintSerializer $sprintSerializer,
        private readonly ProjectSavedViewManager $savedViewManager,
        private readonly ProjectSavedViewRepository $savedViewRepository,
        private readonly ProjectInvoiceManager $invoiceManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly ProjectsViewBuilder $viewBuilder,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly AuditLogSerializer $auditLogSerializer,
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

    #[Route('/{id}/activity', name: '_activity', methods: [HttpMethodEnum::Get->value])]
    public function activity(Project $project): JsonResponse
    {
        $taskIds = array_map(static fn ($task): int => (int) $task->getId(), $project->getTasks()->toArray());
        $columnIds = array_map(static fn ($column): int => (int) $column->getId(), $project->getColumns()->toArray());

        $entries = $this->auditLogRepository->findForProject((int) $project->getId(), $taskIds, $columnIds);

        return $this->jsonSuccess([
            'entries' => array_map($this->auditLogSerializer->serialize(...), $entries),
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

    // ── Labels ───────────────────────────────────────────────────────────────

    #[Route('/{id}/labels', name: '_label_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function createLabel(Project $project, Request $request): JsonResponse
    {
        $input = ProjectLabelInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
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
    public function updateLabel(#[MapEntity(id: 'labelId')] ProjectLabel $label, Request $request): JsonResponse
    {
        $input = ProjectLabelInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $this->labelManager->update($label, $input);

        return $this->jsonSuccess();
    }

    #[Route('/labels/{labelId}/delete', name: '_label_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function deleteLabel(#[MapEntity(id: 'labelId')] ProjectLabel $label): JsonResponse
    {
        $this->labelManager->delete($label);

        return $this->jsonSuccess();
    }

    // ── Task checklist items ─────────────────────────────────────────────────

    #[Route('/tasks/{taskId}/items', name: '_task_items_replace', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function replaceTaskItems(#[MapEntity(id: 'taskId')] ProjectTask $task, Request $request): JsonResponse
    {
        $input = ProjectTaskItemsInput::fromArray($this->decodeJson($request));
        $this->taskItemManager->replaceForTask($task, $input);

        return $this->jsonSuccess();
    }

    // ── Time entries ─────────────────────────────────────────────────────────

    #[Route('/tasks/{taskId}/time-entries', name: '_task_time_entry_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function logTime(#[MapEntity(id: 'taskId')] ProjectTask $task, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->jsonInvalidInput(['_global' => 'backend.projects.errors.time_user_required'], Response::HTTP_OK);
        }

        $input = ProjectTaskTimeEntryInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $entry = $this->timeEntryManager->create($task, $user, $input);

        return $this->jsonSuccess(['entry' => $this->timeEntrySerializer->serialize($entry)]);
    }

    #[Route('/time-entries/{entryId}/delete', name: '_task_time_entry_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function deleteTimeEntry(#[MapEntity(id: 'entryId')] ProjectTaskTimeEntry $entry): JsonResponse
    {
        $this->timeEntryManager->delete($entry);

        return $this->jsonSuccess();
    }

    // ── Comments ─────────────────────────────────────────────────────────────

    #[Route('/tasks/{taskId}/comments', name: '_task_comment_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function createComment(#[MapEntity(id: 'taskId')] ProjectTask $task, Request $request): JsonResponse
    {
        $author = $this->getUser();
        if (!$author instanceof User) {
            throw new AccessDeniedHttpException();
        }

        $input = ProjectTaskCommentInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $comment = $this->commentManager->create($task, $author, $input);

        return $this->jsonSuccess(['comment' => $this->commentSerializer->serialize($comment)]);
    }

    #[Route('/comments/{commentId}/delete', name: '_task_comment_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function deleteComment(#[MapEntity(id: 'commentId')] ProjectTaskComment $comment): JsonResponse
    {
        $this->commentManager->delete($comment);

        return $this->jsonSuccess();
    }

    // ── Attachments ──────────────────────────────────────────────────────────

    #[Route('/tasks/{taskId}/attachments', name: '_task_attachments_attach', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function attachMedia(#[MapEntity(id: 'taskId')] ProjectTask $task, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $mediaIds = array_map(intval(...), (array) ($data['mediaIds'] ?? []));
        $count = $this->attachmentManager->attach($task, $mediaIds);

        return $this->jsonSuccess(['count' => $count]);
    }

    #[Route('/tasks/{taskId}/attachments/{mediaId}', name: '_task_attachment_detach', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function detachMedia(
        #[MapEntity(id: 'taskId')]
        ProjectTask $task,
        #[MapEntity(id: 'mediaId')]
        Media $media,
    ): JsonResponse {
        $this->attachmentManager->detach($task, $media);

        return $this->jsonSuccess();
    }

    // ── Sprints ──────────────────────────────────────────────────────────────

    #[Route('/{id}/sprints', name: '_sprint_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function createSprint(Project $project, Request $request): JsonResponse
    {
        $input = ProjectSprintInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $sprint = $this->sprintManager->create($project, $input);

        return $this->jsonSuccess(['sprint' => $this->sprintSerializer->serialize($sprint)]);
    }

    #[Route('/sprints/{sprintId}/update', name: '_sprint_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function updateSprint(#[MapEntity(id: 'sprintId')] ProjectSprint $sprint, Request $request): JsonResponse
    {
        $input = ProjectSprintInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $this->sprintManager->update($sprint, $input);

        return $this->jsonSuccess();
    }

    #[Route('/sprints/{sprintId}/delete', name: '_sprint_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function deleteSprint(#[MapEntity(id: 'sprintId')] ProjectSprint $sprint): JsonResponse
    {
        $this->sprintManager->delete($sprint);

        return $this->jsonSuccess();
    }

    // ── Saved views ──────────────────────────────────────────────────────────

    #[Route('/{id}/saved-views', name: '_saved_view_list', methods: [HttpMethodEnum::Get->value])]
    public function listSavedViews(Project $project): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }

        $views = $this->savedViewRepository->findForUserAndProject($user, $project);

        return $this->jsonSuccess([
            'views' => array_map(static fn (ProjectSavedView $view): array => [
                'id' => $view->getId(),
                'name' => $view->getName(),
                'filters' => $view->getFilters(),
            ], $views),
        ]);
    }

    #[Route('/{id}/saved-views', name: '_saved_view_create', methods: [HttpMethodEnum::Post->value])]
    public function createSavedView(Project $project, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }

        $data = $this->decodeJson($request);
        $name = mb_trim((string) ($data['name'] ?? ''));
        $filters = (array) ($data['filters'] ?? []);
        if ('' === $name) {
            return $this->jsonInvalidInput(['name' => 'backend.projects.errors.saved_view_name_required'], Response::HTTP_OK);
        }

        $view = $this->savedViewManager->create($user, $project, $name, $filters);

        return $this->jsonSuccess(['view' => [
            'id' => $view->getId(),
            'name' => $view->getName(),
            'filters' => $view->getFilters(),
        ]]);
    }

    #[Route('/saved-views/{viewId}/delete', name: '_saved_view_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteSavedView(#[MapEntity(id: 'viewId')] ProjectSavedView $view): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || $view->getOwner()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException();
        }

        $this->savedViewManager->delete($view);

        return $this->jsonSuccess();
    }

    // ── Invoice generation ──────────────────────────────────────────────────

    #[Route('/{id}/generate-invoice', name: '_generate_invoice', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.projects.edit')]
    public function generateInvoice(Project $project): JsonResponse
    {
        $invoice = $this->invoiceManager->generate($project);

        return $this->jsonSuccess([
            'invoiceId' => $invoice->getId(),
        ]);
    }
}
