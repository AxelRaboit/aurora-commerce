<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Validation\Dto;

use Aurora\Core\Validation\Dto\PaginationRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class PaginationRequestTest extends TestCase
{
    public function testFromRequestUsesDefaults(): void
    {
        $request = new Request();

        $pagination = PaginationRequest::fromRequest($request);

        self::assertSame(1, $pagination->page);
        self::assertSame(20, $pagination->limit);
        self::assertNull($pagination->search);
    }

    public function testFromRequestReadsPageFromQuery(): void
    {
        $request = new Request(['page' => '3']);

        $pagination = PaginationRequest::fromRequest($request);

        self::assertSame(3, $pagination->page);
    }

    public function testFromRequestClampsPageToMinimumOne(): void
    {
        $request = new Request(['page' => '-5']);

        $pagination = PaginationRequest::fromRequest($request);

        self::assertSame(1, $pagination->page);
    }

    public function testFromRequestReadsSearchTrimmed(): void
    {
        $request = new Request(['search' => '  hello  ']);

        $pagination = PaginationRequest::fromRequest($request);

        self::assertSame('hello', $pagination->search);
    }

    public function testFromRequestEmptySearchIsNull(): void
    {
        $request = new Request(['search' => '   ']);

        self::assertNull(PaginationRequest::fromRequest($request)->search);
    }

    public function testFromRequestCustomLimit(): void
    {
        $request = new Request();

        $pagination = PaginationRequest::fromRequest($request, 50);

        self::assertSame(50, $pagination->limit);
    }

    public function testConstructorAssignsValues(): void
    {
        $pagination = new PaginationRequest(2, 30, 'query');

        self::assertSame(2, $pagination->page);
        self::assertSame(30, $pagination->limit);
        self::assertSame('query', $pagination->search);
    }
}
