<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Project\Dto\ProjectColumnInput;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Manager\ProjectColumnManager;
use Aurora\Module\Project\Repository\ProjectColumnRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class ProjectColumnManagerTest extends TestCase
{
    private EntityManagerInterface $em;
    private ProjectColumnRepository $columnRepository;
    private ProjectColumnManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->columnRepository = $this->createMock(ProjectColumnRepository::class);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        // SequenceGenerator is final; use a real instance with stubbed Connection.
        $sequenceGenerator = new SequenceGenerator($this->createStub(Connection::class));

        $this->manager = new ProjectColumnManager(
            $this->em,
            $this->columnRepository,
            new AuditLogger($this->em, $security, new SequenceGenerator($this->createStub(Connection::class)), $this->createStub(SettingRepository::class)),
            $sequenceGenerator,
            $translator,
        );
    }

    private function makeProject(int $id = 1): Project
    {
        $project = new Project();
        (new ReflectionProperty(Project::class, 'id'))->setValue($project, $id);

        return $project;
    }

    private function makeColumn(int $id, Project $project, string $label = 'Col', int $position = 0): ProjectColumn
    {
        $column = new ProjectColumn();
        $column->setProject($project)->setLabel($label)->setPosition($position);
        (new ReflectionProperty(ProjectColumn::class, 'id'))->setValue($column, $id);

        return $column;
    }

    public function testSeedDefaultsCreatesThreeTranslatedColumns(): void
    {
        $project = $this->makeProject();
        $persisted = [];
        $this->em->expects(self::exactly(3))->method('persist')->willReturnCallback(
            function (object $entity) use (&$persisted): void { $persisted[] = $entity; },
        );

        $columns = $this->manager->seedDefaults($project);

        self::assertCount(3, $columns);
        self::assertSame(0, $columns[0]->getPosition());
        self::assertSame(1, $columns[1]->getPosition());
        self::assertSame(2, $columns[2]->getPosition());
        // Translator stub returns the key — assert we hit the right keys.
        self::assertSame('backend.projects.columns.defaults.todo', $columns[0]->getLabel());
        self::assertSame('backend.projects.columns.defaults.in_progress', $columns[1]->getLabel());
        self::assertSame('backend.projects.columns.defaults.done', $columns[2]->getLabel());
    }

    public function testCreatePlacesNewColumnAtNextPosition(): void
    {
        $project = $this->makeProject();
        $existing = [
            $this->makeColumn(1, $project, 'A', 0),
            $this->makeColumn(2, $project, 'B', 1),
        ];
        $this->columnRepository->method('findByProject')->willReturn($existing);

        $column = $this->manager->create($project, new ProjectColumnInput(label: 'New'));

        self::assertSame('New', $column->getLabel());
        self::assertSame(2, $column->getPosition());
    }

    public function testDeleteThrowsWhenLastColumn(): void
    {
        $project = $this->makeProject();
        $only = $this->makeColumn(1, $project);
        $this->columnRepository->method('findByProject')->willReturn([$only]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('backend.projects.errors.column_last_one');

        $this->manager->delete($only);
    }

    public function testDeleteMigratesTasksToFirstRemainingColumn(): void
    {
        $project = $this->makeProject();
        $toDelete = $this->makeColumn(1, $project, 'Old', 0);
        $fallback = $this->makeColumn(2, $project, 'Keep', 1);
        $other = $this->makeColumn(3, $project, 'Done', 2);

        $task1 = new ProjectTask();
        $task1->setColumn($toDelete);
        $task2 = new ProjectTask();
        $task2->setColumn($toDelete);
        $toDelete->getTasks()->add($task1);
        $toDelete->getTasks()->add($task2);

        $this->columnRepository->method('findByProject')->willReturn([$toDelete, $fallback, $other]);
        $this->em->expects(self::once())->method('remove')->with($toDelete);

        $this->manager->delete($toDelete);

        self::assertSame($fallback, $task1->getColumn());
        self::assertSame($fallback, $task2->getColumn());
        // Remaining columns get re-packed positions 0, 1.
        self::assertSame(0, $fallback->getPosition());
        self::assertSame(1, $other->getPosition());
    }

    public function testReorderUpdatesPositionsForKnownIds(): void
    {
        $project = $this->makeProject();
        $colA = $this->makeColumn(10, $project, 'A', 0);
        $colB = $this->makeColumn(20, $project, 'B', 1);
        $this->columnRepository->method('findByProject')->willReturn([$colA, $colB]);
        $this->em->expects(self::atLeastOnce())->method('flush');

        $this->manager->reorder($project, [20, 10]);

        self::assertSame(0, $colB->getPosition());
        self::assertSame(1, $colA->getPosition());
    }
}
