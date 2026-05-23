<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\WorkflowStepTaskTemplate\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Dto\WeldingWorkflowStepTaskTemplateInput;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplate;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Manager\WeldingWorkflowStepTaskTemplateManager;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Repository\WeldingWorkflowStepTaskTemplateRepository;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplate;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WeldingWorkflowStepTemplateRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class WeldingWorkflowStepTaskTemplateManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private WeldingWorkflowStepTemplateRepository $stepRepository;
    private WeldingWorkflowStepTaskTemplateRepository $taskRepository;
    private WeldingWorkflowStepTaskTemplateManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->stepRepository = $this->createMock(WeldingWorkflowStepTemplateRepository::class);
        $this->taskRepository = $this->createMock(WeldingWorkflowStepTaskTemplateRepository::class);
        $this->manager = $this->makeManager();
    }

    private function makeManager(): WeldingWorkflowStepTaskTemplateManager
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $dbalResult = $this->createStub(Result::class);
        $dbalResult->method('fetchOne')->willReturn(1);

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($dbalResult);

        return new WeldingWorkflowStepTaskTemplateManager(
            $this->entityManager,
            $this->stepRepository,
            $this->taskRepository,
            new AuditLogger(
                $this->entityManager,
                $security,
                new SequenceGenerator($connection),
                $this->createStub(SettingRepository::class),
            ),
        );
    }

    public function testCreateHydratesAllFields(): void
    {
        $step = new WeldingWorkflowStepTemplate();
        $this->stepRepository->method('find')->willReturn($step);

        $captured = null;
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof WeldingWorkflowStepTaskTemplate) {
                    $captured = $entity;
                }
            }
        );

        $this->manager->create(new WeldingWorkflowStepTaskTemplateInput(
            workflowStepTemplateId: 1,
            label: 'Check gas pressure',
            description: 'Use the bench gauge',
            position: 2,
            required: false,
        ));

        self::assertNotNull($captured);
        self::assertSame($step, $captured->getWorkflowStepTemplate());
        self::assertSame('Check gas pressure', $captured->getLabel());
        self::assertSame('Use the bench gauge', $captured->getDescription());
        self::assertSame(2, $captured->getPosition());
        self::assertFalse($captured->getRequired());
    }

    public function testCreateThrowsWhenStepNotFound(): void
    {
        $this->stepRepository->method('find')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->manager->create(new WeldingWorkflowStepTaskTemplateInput(
            workflowStepTemplateId: 999,
            label: 'X',
        ));
    }

    public function testReorderReassignsPositionsInOrderedIdsOrder(): void
    {
        $t1 = new WeldingWorkflowStepTaskTemplate();
        $t1->setLabel('A')->setPosition(0);
        $t2 = new WeldingWorkflowStepTaskTemplate();
        $t2->setLabel('B')->setPosition(1);
        $t3 = new WeldingWorkflowStepTaskTemplate();
        $t3->setLabel('C')->setPosition(2);

        // Use reflection to set the otherwise-protected id for predictable matching
        $setId = function (WeldingWorkflowStepTaskTemplate $entity, int $id): void {
            $ref = new ReflectionClass($entity);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($entity, $id);
        };
        $setId($t1, 1);
        $setId($t2, 2);
        $setId($t3, 3);

        $this->taskRepository->method('findBy')->willReturn([$t1, $t2, $t3]);

        $this->manager->reorder(42, [3, 1, 2]);

        self::assertSame(1, $t1->getPosition()); // moved from 0 → 1
        self::assertSame(2, $t2->getPosition()); // moved from 1 → 2
        self::assertSame(0, $t3->getPosition()); // moved from 2 → 0
    }
}
