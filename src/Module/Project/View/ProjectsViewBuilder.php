<?php

declare(strict_types=1);

namespace Aurora\Module\Project\View;

use Aurora\Core\Reference\EntityReferenceResolver;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use Aurora\Module\Project\Repository\ProjectRepository;
use Aurora\Module\Project\Serializer\ProjectSerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ProjectsViewBuilder
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityReferenceResolver $referenceResolver,
        private ProjectRepository $projectRepository,
        private ProjectSerializerInterface $projectSerializer,
        private TranslatorInterface $translator,
    ) {}

    /**
     * @return array{success: bool, items: list<array<string, mixed>>, total: int, page: int, totalPages: int, status: ?string}
     */
    public function buildListPayload(PaginationRequest $pagination, Request $request): array
    {
        $status = $this->resolveStatus($request->query->get('status'));
        $result = $this->projectRepository->findPaginated(
            $pagination->page,
            search: $pagination->search,
            status: $status,
        );

        return [
            'success' => true,
            'items' => array_map($this->projectSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'status' => $status?->value,
        ];
    }

    private function resolveStatus(?string $value): ?ProjectStatusEnum
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return ProjectStatusEnum::tryFrom($value);
    }

    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        $users = array_map(
            static fn ($user): array => ['id' => $user->getId(), 'name' => $user->getName()],
            $this->userRepository->findAllAdminsAlphabetical(),
        );

        $translator = $this->translator;

        $statusOptions = array_map(
            static fn (ProjectStatusEnum $status): array => ['value' => $status->value, 'label' => $translator->trans($status->getLabelKey())],
            ProjectStatusEnum::cases(),
        );

        $priorityOptions = array_map(
            static fn (ProjectTaskPriorityEnum $priority): array => ['value' => $priority->value, 'label' => $translator->trans($priority->getLabelKey())],
            ProjectTaskPriorityEnum::cases(),
        );

        // CRM picker options come from the core resolver (empty when Crm absent).
        return [
            'statusOptions' => $statusOptions,
            'priorityOptions' => $priorityOptions,
            'users' => $users,
            'crmContacts' => $this->referenceResolver->options('crm.contact'),
            'crmCompanies' => $this->referenceResolver->options('crm.company'),
            'crmDeals' => $this->referenceResolver->options('crm.deal'),
        ];
    }
}
