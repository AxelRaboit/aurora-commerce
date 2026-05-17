<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Project\Dto\ProjectLabelInputInterface;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectLabel;
use Aurora\Module\Project\Manager\ProjectLabelManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ProjectLabelManagerTest extends TestCase
{
    private function makeInput(string $name = 'Bug', string $color = 'red'): ProjectLabelInputInterface
    {
        $input = $this->createStub(ProjectLabelInputInterface::class);
        $input->method('getName')->willReturn($name);
        $input->method('getColor')->willReturn($color);

        return $input;
    }

    public function testCreatePersistsAndAudits(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');

        $auditLogger = $this->createMock(AuditLogger::class);
        $auditLogger->expects(self::once())
            ->method('log')
            ->with('project', 'label.created', 'ProjectLabel', self::anything(), self::anything());

        $manager = new ProjectLabelManager($em, $auditLogger);

        $project = new Project();
        $label = $manager->create($project, $this->makeInput('Critical', 'red'));

        self::assertInstanceOf(ProjectLabel::class, $label);
        self::assertSame($project, $label->getProject());
        self::assertSame('Critical', $label->getName());
        self::assertSame('red', $label->getColor());
    }

    public function testUpdateChangesNameAndColorAndAudits(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $auditLogger = $this->createMock(AuditLogger::class);
        $auditLogger->expects(self::once())
            ->method('log')
            ->with('project', 'label.updated', 'ProjectLabel', self::anything(), self::anything());

        $label = (new ProjectLabel())->setProject(new Project());

        (new ProjectLabelManager($em, $auditLogger))->update($label, $this->makeInput('Updated', 'blue'));

        self::assertSame('Updated', $label->getName());
        self::assertSame('blue', $label->getColor());
    }

    public function testDeleteRemovesAndAudits(): void
    {
        $label = (new ProjectLabel())->setProject(new Project())->setName('Bug')->setColor('red');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('remove')->with($label);
        $em->expects(self::once())->method('flush');

        $auditLogger = $this->createMock(AuditLogger::class);
        $auditLogger->expects(self::once())
            ->method('log')
            ->with('project', 'label.deleted', 'ProjectLabel', self::anything(), self::anything());

        (new ProjectLabelManager($em, $auditLogger))->delete($label);
    }
}
