<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Repository\DocumentRepository;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Manager\ProjectTaskAttachmentManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ProjectTaskAttachmentManagerTest extends TestCase
{
    private function makeDocument(int $id): DocumentInterface
    {
        $document = $this->createStub(DocumentInterface::class);
        $document->method('getId')->willReturn($id);

        return $document;
    }

    public function testAttachReturnsZeroForEmptyArray(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $repo = $this->createStub(DocumentRepository::class);
        $audit = $this->createMock(AuditLogger::class);
        $audit->expects(self::never())->method('log');

        $manager = new ProjectTaskAttachmentManager($em, $repo, $audit);
        $task = (new ProjectTask())->setProject(new Project());

        self::assertSame(0, $manager->attach($task, []));
    }

    public function testAttachAddsNewDocuments(): void
    {
        $document1 = $this->makeDocument(1);
        $document2 = $this->makeDocument(2);

        $repo = $this->createStub(DocumentRepository::class);
        $repo->method('findBy')->willReturn([$document1, $document2]);

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

    public function testAttachSkipsExistingDocuments(): void
    {
        $existing = $this->makeDocument(1);
        $new = $this->makeDocument(2);

        $repo = $this->createStub(DocumentRepository::class);
        $repo->method('findBy')->willReturn([$existing, $new]);

        $em = $this->createStub(EntityManagerInterface::class);
        $audit = $this->createStub(AuditLogger::class);

        $task = (new ProjectTask())->setProject(new Project());
        $task->addAttachment($existing);

        $manager = new ProjectTaskAttachmentManager($em, $repo, $audit);
        $added = $manager->attach($task, [1, 2]);

        self::assertSame(1, $added, 'only new documents counted');
    }

    public function testDetachDoesNothingForUnknownDocument(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects(self::never())->method('log');

        $task = (new ProjectTask())->setProject(new Project());

        $manager = new ProjectTaskAttachmentManager($em, $this->createStub(DocumentRepository::class), $audit);
        $manager->detach($task, $this->makeDocument(1));
    }

    public function testDetachRemovesDocumentAndAudits(): void
    {
        $document = $this->makeDocument(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects(self::once())
            ->method('log')
            ->with('project', 'task.attachment.removed', 'ProjectTask', self::anything(), self::anything());

        $task = (new ProjectTask())->setProject(new Project());
        $task->addAttachment($document);

        $manager = new ProjectTaskAttachmentManager($em, $this->createStub(DocumentRepository::class), $audit);
        $manager->detach($task, $document);

        self::assertCount(0, $task->getAttachments());
    }
}
