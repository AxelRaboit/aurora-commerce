<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Hr\Employee\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Hr\Employee\Entity\Employee;
use Aurora\Module\Hr\Employee\Repository\EmployeeRepository;
use Aurora\Module\Hr\Employee\Serializer\EmployeeSerializerInterface;
use Aurora\Module\Hr\Employee\View\EmployeesViewBuilder;
use PHPUnit\Framework\TestCase;

final class EmployeesViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsEmployeesAndSearch(): void
    {
        $repo = $this->createStub(EmployeeRepository::class);
        $repo->method('findPaginated')->willReturn([
            'items' => [new Employee()],
            'total' => 1,
            'page' => 1,
            'totalPages' => 1,
        ]);

        $serializer = $this->createStub(EmployeeSerializerInterface::class);
        $serializer->method('serialize')->willReturn(['id' => 1]);

        $pagination = new PaginationRequest(1, 20, 'jane');
        $view = (new EmployeesViewBuilder($repo, $serializer))->indexView($pagination);

        self::assertArrayHasKey('employees', $view);
        self::assertSame('jane', $view['search']);
    }

    public function testBuildListPayloadReturnsPaginatedShape(): void
    {
        $repo = $this->createStub(EmployeeRepository::class);
        $repo->method('findPaginated')->willReturn([
            'items' => [new Employee(), new Employee()],
            'total' => 2,
            'page' => 1,
            'totalPages' => 1,
        ]);

        $serializer = $this->createStub(EmployeeSerializerInterface::class);
        $serializer->method('serialize')->willReturn(['id' => 1]);

        $pagination = new PaginationRequest(1, 20, null);
        $payload = (new EmployeesViewBuilder($repo, $serializer))->buildListPayload($pagination);

        self::assertTrue($payload['success']);
        self::assertCount(2, $payload['items']);
        self::assertSame(2, $payload['total']);
    }
}
