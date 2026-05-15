<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Dto;

use Aurora\Module\Project\Dto\ProjectInputFactory;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use PHPUnit\Framework\TestCase;

final class ProjectInputTest extends TestCase
{
    public function testFromArrayDefaultsStatusToDraftWhenMissing(): void
    {
        $input = (new ProjectInputFactory())->fromArray(['title' => 'X']);

        self::assertSame(ProjectStatusEnum::Draft->value, $input->status);
        self::assertSame('X', $input->title);
    }

    public function testFromArrayTrimsTitleAndDescription(): void
    {
        $input = (new ProjectInputFactory())->fromArray([
            'title' => '  Refonte  ',
            'description' => '  desc  ',
        ]);

        self::assertSame('Refonte', $input->title);
        self::assertSame('desc', $input->description);
    }

    public function testNormalizeIdListFiltersInvalidValues(): void
    {
        $input = (new ProjectInputFactory())->fromArray([
            'title' => 'X',
            'crmContactIds' => [1, '2', '', null, 0, -3, 5, 5, '7 '],
        ]);

        // Filters: empty string, null, 0, negative; dedupes; trims numeric strings → int.
        self::assertSame([1, 2, 5, 7], $input->crmContactIds);
    }

    public function testNormalizeIdListReturnsEmptyForNonArray(): void
    {
        $input = (new ProjectInputFactory())->fromArray(['title' => 'X', 'crmContactIds' => 'not-an-array']);
        self::assertSame([], $input->crmContactIds);
    }

    public function testCrmFieldsAreCoercedFromStrings(): void
    {
        $input = (new ProjectInputFactory())->fromArray([
            'title' => 'X',
            'responsibleUserId' => '12',
            'crmCompanyId' => '7',
            'crmDealId' => '99',
        ]);

        self::assertSame(12, $input->responsibleUserId);
        self::assertSame(7, $input->crmCompanyId);
        self::assertSame(99, $input->crmDealId);
    }

    public function testEmptyStringsBecomeNullForOptionalIds(): void
    {
        $input = (new ProjectInputFactory())->fromArray([
            'title' => 'X',
            'responsibleUserId' => '',
            'crmDealId' => '',
        ]);

        self::assertNull($input->responsibleUserId);
        self::assertNull($input->crmDealId);
    }

    public function testStatusEnumReturnsTypedValue(): void
    {
        $input = (new ProjectInputFactory())->fromArray(['title' => 'X', 'status' => 'active']);
        self::assertSame(ProjectStatusEnum::Active, $input->getStatusEnum());
    }

    public function testStartDateAndEndDateAreTrimmed(): void
    {
        $input = (new ProjectInputFactory())->fromArray([
            'title' => 'X',
            'startDate' => '  2026-01-01  ',
            'endDate' => '  2026-12-31  ',
        ]);

        self::assertSame('2026-01-01', $input->startDate);
        self::assertSame('2026-12-31', $input->endDate);
    }

    public function testStartDateAndEndDateAreNullByDefault(): void
    {
        $input = (new ProjectInputFactory())->fromArray(['title' => 'X']);

        self::assertNull($input->startDate);
        self::assertNull($input->endDate);
    }

    public function testGettersReturnConstructorValues(): void
    {
        $input = (new ProjectInputFactory())->fromArray([
            'title' => 'Refonte',
            'description' => 'desc',
            'status' => 'active',
            'startDate' => '2026-01-01',
            'endDate' => '2026-12-31',
            'responsibleUserId' => 1,
            'crmContactIds' => [10, 20],
            'crmCompanyId' => 5,
            'crmDealId' => 9,
        ]);

        self::assertSame('Refonte', $input->getTitle());
        self::assertSame('desc', $input->getDescription());
        self::assertSame('active', $input->getStatus());
        self::assertSame('2026-01-01', $input->getStartDate());
        self::assertSame('2026-12-31', $input->getEndDate());
        self::assertSame(1, $input->getResponsibleUserId());
        self::assertSame([10, 20], $input->getCrmContactIds());
        self::assertSame(5, $input->getCrmCompanyId());
        self::assertSame(9, $input->getCrmDealId());
    }
}
