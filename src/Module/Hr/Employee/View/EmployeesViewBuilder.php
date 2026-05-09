<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Employee\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Hr\Employee\Repository\EmployeeRepository;
use Aurora\Module\Hr\Employee\Serializer\EmployeeSerializerInterface;

class EmployeesViewBuilder
{
    public function __construct(
        protected readonly EmployeeRepository $employeeRepository,
        protected readonly EmployeeSerializerInterface $employeeSerializer,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(PaginationRequest $pagination): array
    {
        return [
            'employees' => $this->buildListPayload($pagination),
            'search' => $pagination->search ?? '',
        ];
    }

    /** @return array<string, mixed> */
    public function buildListPayload(PaginationRequest $pagination): array
    {
        $result = $this->employeeRepository->findPaginated($pagination->page, search: $pagination->search);

        return [
            'success' => true,
            'items' => array_map($this->employeeSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
