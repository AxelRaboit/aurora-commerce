<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Block\Service;

use Aurora\Module\Notes\Block\Entity\BlockNote;
use Aurora\Module\Notes\Block\Entity\BlockNoteInterface;
use Aurora\Module\Notes\Block\Service\BlockNoteHierarchyService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class BlockNoteHierarchyServiceTest extends TestCase
{
    private BlockNoteHierarchyService $service;

    protected function setUp(): void
    {
        $this->service = new BlockNoteHierarchyService();
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

    private function makeNote(int $id): BlockNoteInterface
    {
        $note = new BlockNote();
        (new ReflectionProperty(BlockNote::class, 'id'))->setValue($note, $id);

        return $note;
    }
}
