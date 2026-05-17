<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\DocumentTag\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Ged\DocumentTag\Dto\DocumentTagInputInterface;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTag;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;
use Aurora\Module\Ged\DocumentTag\Manager\DocumentTagManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class DocumentTagManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private DocumentTagManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->manager = new DocumentTagManager(
            $this->entityManager,
            $this->createStub(AuditLogger::class),
        );
    }

    private function makeInput(string $name, ?string $color): DocumentTagInputInterface
    {
        $input = $this->createStub(DocumentTagInputInterface::class);
        $input->method('getName')->willReturn($name);
        $input->method('getColor')->willReturn($color);

        return $input;
    }

    private function captureTag(mixed &$captured): void
    {
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof DocumentTag) {
                    $captured = $entity;
                }
            }
        );
    }

    public function testCreatePersistsTagWithNameAndColor(): void
    {
        $captured = null;
        $this->captureTag($captured);

        $this->manager->create($this->makeInput('Urgent', '#ff0000'));

        self::assertInstanceOf(DocumentTag::class, $captured);
        self::assertSame('Urgent', $captured->getName());
        self::assertSame('#ff0000', $captured->getColor());
    }

    public function testCreateWithNullColorPersists(): void
    {
        $captured = null;
        $this->captureTag($captured);

        $this->manager->create($this->makeInput('Draft', null));

        self::assertInstanceOf(DocumentTag::class, $captured);
        self::assertNull($captured->getColor());
    }

    public function testCreateCallsPersistAndFlush(): void
    {
        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->create($this->makeInput('Tag', null));
    }

    public function testCreateReturnsPersistedTag(): void
    {
        $result = $this->manager->create($this->makeInput('Invoice', '#00ff00'));

        self::assertInstanceOf(DocumentTagInterface::class, $result);
        self::assertSame('Invoice', $result->getName());
    }

    public function testUpdateAppliesInputToTag(): void
    {
        $tag = new DocumentTag();
        $tag->setName('Old')->setColor('#000000');

        $this->manager->update($tag, $this->makeInput('New', '#ffffff'));

        self::assertSame('New', $tag->getName());
        self::assertSame('#ffffff', $tag->getColor());
    }

    public function testUpdateCallsFlush(): void
    {
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->update(new DocumentTag(), $this->makeInput('X', null));
    }

    public function testDeleteCallsRemoveAndFlush(): void
    {
        $tag = new DocumentTag();
        $tag->setName('ToDelete')->setColor(null);

        $this->entityManager->expects(self::once())->method('remove')->with($tag);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($tag);
    }
}
