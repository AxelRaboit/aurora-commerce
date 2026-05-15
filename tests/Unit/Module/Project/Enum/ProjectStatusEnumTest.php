<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Enum;

use Aurora\Module\Project\Enum\ProjectStatusEnum;
use PHPUnit\Framework\TestCase;

final class ProjectStatusEnumTest extends TestCase
{
    public function testValuesReturnsAllCaseValues(): void
    {
        self::assertSame(['draft', 'active', 'completed', 'cancelled'], ProjectStatusEnum::values());
    }

    public function testGetLabelKeyPrefixesCaseValue(): void
    {
        self::assertSame('backend.projects.status_draft', ProjectStatusEnum::Draft->getLabelKey());
        self::assertSame('backend.projects.status_active', ProjectStatusEnum::Active->getLabelKey());
        self::assertSame('backend.projects.status_completed', ProjectStatusEnum::Completed->getLabelKey());
        self::assertSame('backend.projects.status_cancelled', ProjectStatusEnum::Cancelled->getLabelKey());
    }
}
