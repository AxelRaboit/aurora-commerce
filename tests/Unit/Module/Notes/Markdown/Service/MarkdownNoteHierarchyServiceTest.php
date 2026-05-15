<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Markdown\Service;

use Aurora\Module\Notes\Markdown\Entity\MarkdownNote;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNoteInterface;
use Aurora\Module\Notes\Markdown\Service\MarkdownNoteHierarchyService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class MarkdownNoteHierarchyServiceTest extends TestCase
{
    private MarkdownNoteHierarchyService $service;

    protected function setUp(): void
    {
        $this->service = new MarkdownNoteHierarchyService();
    }

    public function testNoCycleWhenTargetIsUnrelated(): void
    {
        $note = $this->makeNote(1);
        $other = $this->makeNote(2);

        self::assertFalse($this->service->wouldCreateCycle($note, $other));
    }

    public function testCycleWhenTargetIsSelf(): void
    {
        $note = $this->makeNote(1);

        self::assertTrue($this->service->wouldCreateCycle($note, $note));
    }

    public function testCycleWhenTargetIsDirectChild(): void
    {
        $parent = $this->makeNote(1);
        $child = $this->makeNote(2);
        $child->setParent($parent);

        self::assertTrue($this->service->wouldCreateCycle($parent, $child));
    }

    public function testCycleWhenTargetIsGrandchild(): void
    {
        $a = $this->makeNote(1);
        $b = $this->makeNote(2);
        $b->setParent($a);
        $c = $this->makeNote(3);
        $c->setParent($b);

        self::assertTrue($this->service->wouldCreateCycle($a, $c));
    }

    public function testNoCycleWhenMovingChildElsewhere(): void
    {
        $parent = $this->makeNote(1);
        $child = $this->makeNote(2);
        $child->setParent($parent);
        $unrelated = $this->makeNote(3);

        self::assertFalse($this->service->wouldCreateCycle($child, $unrelated));
    }

    private function makeNote(int $id): MarkdownNoteInterface
    {
        $note = new MarkdownNote();
        (new ReflectionProperty(MarkdownNote::class, 'id'))->setValue($note, $id);

        return $note;
    }
}
