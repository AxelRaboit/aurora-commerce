<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Media\Library\Entity\MediaInterface;
use Aurora\Module\Media\Library\Repository\MediaRepository;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Manager\ProjectTaskAttachmentManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ProjectTaskAttachmentManagerTest extends TestCase
{
    private function makeMedia(int $id): MediaInterface
    {
        $media = $this->createStub(MediaInterface::class);
        $media->method('getId')->willReturn($id);

        return $media;
    }

    public function testAttachReturnsZeroForEmptyArray(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $repo = $this->createStub(MediaRepository::class);
        $audit = $this->createMock(AuditLogger::class);
        $audit->expects(self::never())->method('log');

        $manager = new ProjectTaskAttachmentManager($em, $repo, $audit);
        $task = (new ProjectTask())->setProject(new Project());

        self::assertSame(0, $manager->attach($task, []));
    }

    public function testAttachAddsNewMedia(): void
    {
        $media1 = $this->makeMedia(1);
        $media2 = $this->makeMedia(2);

        $repo = $this->createStub(MediaRepository::class);
        $repo->method('findBy')->willReturn([$media1, $media2]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects(self::once())
            ->method('log')
            ->with('project', 'task.attachment.added', 'ProjectTask', self::anything(), self::anything());

        $manager = new ProjectTaskAttachmentManager($em, $repo, $audit);
        $task = (new ProjectTask())->setProject(new Project());

        $added = $manager->attach($task, [1, 2]);

        self::assertSame(2, $added);
        self::assertCount(2, $task->getAttachments());
    }

    public function testAttachSkipsExistingMedia(): void
    {
        $existing = $this->makeMedia(1);
        $new = $this->makeMedia(2);

        $repo = $this->createStub(MediaRepository::class);
        $repo->method('findBy')->willReturn([$existing, $new]);

        $em = $this->createStub(EntityManagerInterface::class);
        $audit = $this->createStub(AuditLogger::class);

        $task = (new ProjectTask())->setProject(new Project());
        $task->addAttachment($existing);

        $manager = new ProjectTaskAttachmentManager($em, $repo, $audit);
        $added = $manager->attach($task, [1, 2]);

        self::assertSame(1, $added, 'only new media counted');
    }

    public function testDetachDoesNothingForUnknownMedia(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects(self::never())->method('log');

        $task = (new ProjectTask())->setProject(new Project());

        $manager = new ProjectTaskAttachmentManager($em, $this->createStub(MediaRepository::class), $audit);
        $manager->detach($task, $this->makeMedia(1));
    }

    public function testDetachRemovesMediaAndAudits(): void
    {
        $media = $this->makeMedia(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects(self::once())
            ->method('log')
            ->with('project', 'task.attachment.removed', 'ProjectTask', self::anything(), self::anything());

        $task = (new ProjectTask())->setProject(new Project());
        $task->addAttachment($media);

        $manager = new ProjectTaskAttachmentManager($em, $this->createStub(MediaRepository::class), $audit);
        $manager->detach($task, $media);

        self::assertCount(0, $task->getAttachments());
    }
}
