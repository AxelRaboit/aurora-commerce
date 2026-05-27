<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Dev\Audit\Repository\AuditLogRepository;
use Aurora\Module\Dev\Audit\Serializer\AuditLogSerializer;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Project\Dto\ProjectInputFactoryInterface;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectSavedView;
use Aurora\Module\Project\Manager\ProjectInvoiceManager;
use Aurora\Module\Project\Manager\ProjectManager;
use Aurora\Module\Project\Manager\ProjectSavedViewManager;
use Aurora\Module\Project\Repository\ProjectSavedViewRepository;
use Aurora\Module\Project\Serializer\ProjectSerializer;
use Aurora\Module\Project\Serializer\ProjectTaskSerializer;
use Aurora\Module\Project\View\ProjectsViewBuilder;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Project root CRUD + activity + saved views + invoice generation.
 * Sub-domains (tasks, columns, labels, sprints) live in sibling
 * controllers — see `ProjectTasksController`, `ProjectColumnsController`,
 * `ProjectLabelsController`, `ProjectSprintsController`.
 */
#[Route('/backend/project/projects', name: 'backend_project_projects')]
#[IsGranted('project.projects.view')]
final class ProjectsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly ProjectSerializer $projectSerializer,
        private readonly ProjectTaskSerializer $taskSerializer,
        private readonly ProjectManager $projectManager,
        private readonly ProjectSavedViewManager $savedViewManager,
        private readonly ProjectSavedViewRepository $savedViewRepository,
        private readonly ProjectInvoiceManager $invoiceManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly ProjectsViewBuilder $viewBuilder,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly AuditLogSerializer $auditLogSerializer,
        private readonly ProjectInputFactoryInterface $projectInputFactory,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Project/backend/projects/index.html.twig', $this->viewBuilder->indexView());
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
        $input = $this->projectInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $project = $this->projectManager->create($input);

        return $this->jsonSuccess(['project' => $this->projectSerializer->serialize($project)]);
    }

    #[Route('/{id}', name: '_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function show(Project $project): JsonResponse
    {
        $tasks = array_map($this->taskSerializer->serialize(...), $project->getTasks()->toArray());

        return $this->jsonSuccess([
            'project' => $this->projectSerializer->serialize($project),
            'tasks' => $tasks,
        ]);
    }

    #[Route('/{id}/activity', name: '_activity', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function activity(Project $project): JsonResponse
    {
        $taskIds = array_map(static fn ($task): int => (int) $task->getId(), $project->getTasks()->toArray());
        $columnIds = array_map(static fn ($column): int => (int) $column->getId(), $project->getColumns()->toArray());

        $entries = $this->auditLogRepository->findForProject((int) $project->getId(), $taskIds, $columnIds);

        return $this->jsonSuccess([
            'entries' => array_map($this->auditLogSerializer->serialize(...), $entries),
        ]);
    }

    #[Route('/{id}/update', name: '_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.projects.edit')]
    public function update(Project $project, Request $request): JsonResponse
    {
        $input = $this->projectInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->projectManager->update($project, $input);

        return $this->jsonSuccess(['project' => $this->projectSerializer->serialize($project)]);
    }

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.projects.delete')]
    public function delete(Project $project): JsonResponse
    {
        $this->projectManager->delete($project);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/saved-views', name: '_saved_view_list', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
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

    #[Route('/{id}/saved-views', name: '_saved_view_create', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
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
            return $this->jsonInvalidInput(['name' => 'backend.projects.errors.saved_view_name_required']);
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

    #[Route('/{id}/generate-invoice', name: '_generate_invoice', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('project.projects.edit')]
    public function generateInvoice(Project $project): JsonResponse
    {
        $invoice = $this->invoiceManager->generate($project);

        return $this->jsonSuccess([
            'invoiceId' => $invoice->getId(),
        ]);
    }
}
