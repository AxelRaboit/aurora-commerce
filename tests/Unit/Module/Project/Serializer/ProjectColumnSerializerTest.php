<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectColumnInterface;
use Aurora\Module\Project\Serializer\ProjectColumnSerializer;
use PHPUnit\Framework\TestCase;

final class ProjectColumnSerializerTest extends TestCase
{
    public function testSerializeReturnsExpectedShape(): void
    {
        $column = $this->createStub(ProjectColumnInterface::class);
        $column->method('getId')->willReturn(1);
        $column->method('getReference')->willReturn('COL-001');
        $column->method('getLabel')->willReturn('In Progress');
        $column->method('getPosition')->willReturn(2);

        $result = (new ProjectColumnSerializer())->serialize($column);

        self::assertSame(['id' => 1, 'reference' => 'COL-001', 'label' => 'In Progress', 'position' => 2], $result);
    }

    public function testSerializeWithNullReference(): void
    {
        $column = $this->createStub(ProjectColumnInterface::class);
        $column->method('getId')->willReturn(1);
        $column->method('getReference')->willReturn(null);
        $column->method('getLabel')->willReturn('Done');
        $column->method('getPosition')->willReturn(0);

        $result = (new ProjectColumnSerializer())->serialize($column);

        self::assertNull($result['reference']);
    }
}
