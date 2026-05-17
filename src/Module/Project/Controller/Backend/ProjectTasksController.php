<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\Media\Library\Entity\Media;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Project\Dto\ProjectTaskCommentInputFactoryInterface;
use Aurora\Module\Project\Dto\ProjectTaskInputFactoryInterface;
use Aurora\Module\Project\Dto\ProjectTaskItemsInputFactoryInterface;
use Aurora\Module\Project\Dto\ProjectTaskTimeEntryInputFactoryInterface;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskComment;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntry;
use Aurora\Module\Project\Manager\ProjectTaskAttachmentManager;
use Aurora\Module\Project\Manager\ProjectTaskCommentManager;
use Aurora\Module\Project\Manager\ProjectTaskItemManager;
use Aurora\Module\Project\Manager\ProjectTaskManager;
use Aurora\Module\Project\Manager\ProjectTaskTimeEntryManager;
use Aurora\Module\Project\Repository\ProjectColumnRepository;
use Aurora\Module\Project\Serializer\ProjectTaskCommentSerializer;
use Aurora\Module\Project\Serializer\ProjectTaskSerializer;
use Aurora\Module\Project\Serializer\ProjectTaskTimeEntrySerializer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Project tasks sub-domain — task CRUD, reorder, checklist items, time
 * entries, comments and media attachments. Split from `ProjectsController`
 * to keep each controller focused on one sub-domain.
 *
 * All route names preserved (`backend_projects_task_*`,
 * `backend_projects_tasks_reorder`).
 */
#[Route('/backend/projects', name: 'backend_projects')]
#[IsGranted('project.projects.view')]
final class ProjectTasksController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly ProjectTaskManager $taskManager,
        private readonly ProjectTaskSerializer $taskSerializer,
        private readonly ProjectTaskItemManager $taskItemManager,
        private readonly ProjectTaskTimeEntryManager $timeEntryManager,
        private readonly ProjectTaskTimeEntrySerializer $timeEntrySerializer,
        private readonly ProjectTaskCommentManager $commentManager,
        private readonly ProjectTaskCommentSerializer $commentSerializer,
        private readonly ProjectTaskAttachmentManager $attachmentManager,
        private readonly ProjectColumnRepository $columnRepository,
        private readonly PayloadValidator $payloadValidator,
        private readonly ProjectTaskInputFactoryInterface $taskInputFactory,
        private readonly ProjectTaskCommentInputFactoryInterface $commentInputFactory,
        private readonly ProjectTaskItemsInputFactoryInterface $taskItemsInputFactory,
        private readonly ProjectTaskTimeEntryInputFactoryInterface $timeEntryInputFactory,
    ) {}

    #[Route('/{id}/tasks', name: '_task_create', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function createTask(Project $project, Request $request): JsonResponse
    {
        $input = $this->taskInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $task = $this->taskManager->create($project, $input);

        return $this->jsonSuccess(['task' => $this->taskSerializer->serialize($task)]);
    }

    #[Route('/tasks/{taskId}/update', name: '_task_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function updateTask(#[MapEntity(id: 'taskId')] ProjectTask $task, Request $request): JsonResponse
    {
        $input = $this->taskInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
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

    #[Route('/{id}/tasks/reorder', name: '_tasks_reorder', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
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

    #[Route('/tasks/{taskId}/items', name: '_task_items_replace', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function replaceTaskItems(#[MapEntity(id: 'taskId')] ProjectTask $task, Request $request): JsonResponse
    {
        $input = $this->taskItemsInputFactory->fromArray($this->decodeJson($request));
        $this->taskItemManager->replaceForTask($task, $input);

        return $this->jsonSuccess();
    }

    #[Route('/tasks/{taskId}/time-entries', name: '_task_time_entry_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function logTime(#[MapEntity(id: 'taskId')] ProjectTask $task, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->jsonInvalidInput(['_global' => 'backend.projects.errors.time_user_required']);
        }

        $input = $this->timeEntryInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
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

    #[Route('/tasks/{taskId}/comments', name: '_task_comment_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.tasks.manage')]
    public function createComment(#[MapEntity(id: 'taskId')] ProjectTask $task, Request $request): JsonResponse
    {
        $author = $this->getUser();
        if (!$author instanceof User) {
            throw new AccessDeniedHttpException();
        }

        $input = $this->commentInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
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
}
