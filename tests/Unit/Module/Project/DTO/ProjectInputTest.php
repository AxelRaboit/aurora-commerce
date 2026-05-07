<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\DTO;

use Aurora\Module\Project\DTO\ProjectInput;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use PHPUnit\Framework\TestCase;

final class ProjectInputTest extends TestCase
{
    public function testFromArrayDefaultsStatusToDraftWhenMissing(): void
    {
        $input = ProjectInput::fromArray(['title' => 'X']);

        self::assertSame(ProjectStatusEnum::Draft->value, $input->status);
        self::assertSame('X', $input->title);
    }

    public function testFromArrayTrimsTitleAndDescription(): void
    {
        $input = ProjectInput::fromArray([
            'title' => '  Refonte  ',
            'description' => '  desc  ',
        ]);

        self::assertSame('Refonte', $input->title);
        self::assertSame('desc', $input->description);
    }

    public function testNormalizeIdListFiltersInvalidValues(): void
    {
        $input = ProjectInput::fromArray([
            'title' => 'X',
            'crmContactIds' => [1, '2', '', null, 0, -3, 5, 5, '7 '],
        ]);

        // Filters: empty string, null, 0, negative; dedupes; trims numeric strings → int.
        self::assertSame([1, 2, 5, 7], $input->crmContactIds);
    }

    public function testNormalizeIdListReturnsEmptyForNonArray(): void
    {
        $input = ProjectInput::fromArray(['title' => 'X', 'crmContactIds' => 'not-an-array']);
        self::assertSame([], $input->crmContactIds);
    }

    public function testCrmFieldsAreCoercedFromStrings(): void
    {
        $input = ProjectInput::fromArray([
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
        $input = ProjectInput::fromArray([
            'title' => 'X',
            'responsibleUserId' => '',
            'crmDealId' => '',
        ]);

        self::assertNull($input->responsibleUserId);
        self::assertNull($input->crmDealId);
    }

    public function testStatusEnumReturnsTypedValue(): void
    {
        $input = ProjectInput::fromArray(['title' => 'X', 'status' => 'active']);
        self::assertSame(ProjectStatusEnum::Active, $input->statusEnum());
    }
}
