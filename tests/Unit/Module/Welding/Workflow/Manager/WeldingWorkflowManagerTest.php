<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\Workflow\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Hr\Employee\Entity\Employee;
use Aurora\Module\Hr\Employee\Repository\EmployeeRepository;
use Aurora\Module\Welding\Enum\WeldingWorkflowStatusEnum;
use Aurora\Module\Welding\Workflow\Dto\WeldingWorkflowInput;
use Aurora\Module\Welding\Workflow\Entity\WeldingWorkflow;
use Aurora\Module\Welding\Workflow\Manager\WeldingWorkflowManager;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStep;
use Aurora\Module\Welding\WorkflowStepTask\Entity\WeldingWorkflowStepTask;
use Aurora\Module\Welding\WorkflowStepTask\Manager\WeldingWorkflowStepTaskManagerInterface;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplate;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplate;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplate;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WeldingWorkflowTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use IteratorAggregate;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class WeldingWorkflowManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private WeldingWorkflowTemplateRepository $templateRepository;
    private EmployeeRepository $employeeRepository;
    private SettingRepository $settingRepository;
    private WeldingWorkflowStepTaskManagerInterface $taskManager;
    private WeldingWorkflowManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->templateRepository = $this->createMock(WeldingWorkflowTemplateRepository::class);
        $this->employeeRepository = $this->createMock(EmployeeRepository::class);
        $this->settingRepository = $this->createMock(SettingRepository::class);
        $this->settingRepository->method('getOrDefault')->willReturn('WLD');
        $this->taskManager = $this->createMock(WeldingWorkflowStepTaskManagerInterface::class);
        $this->manager = $this->makeManager();
    }

    private function makeManager(): WeldingWorkflowManager
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $dbalResult = $this->createStub(Result::class);
        $dbalResult->method('fetchOne')->willReturn(42);

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($dbalResult);

        return new WeldingWorkflowManager(
            $this->entityManager,
            $this->templateRepository,
            $this->employeeRepository,
            $this->settingRepository,
            new SequenceGenerator($connection),
            new AuditLogger(
                $this->entityManager,
                $security,
                new SequenceGenerator($connection),
                $this->createStub(SettingRepository::class),
            ),
            $this->taskManager,
        );
    }

    public function testCreateGeneratesReferenceAndStartsInDraft(): void
    {
        $template = new WeldingWorkflowTemplate();
        $template->setTitle('DMOS');
        $this->templateRepository->method('find')->willReturn($template);

        $captured = null;
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof WeldingWorkflow) {
                    $captured = $entity;
                }
            }
        );

        $result = $this->manager->create(new WeldingWorkflowInput(templateId: 1));

        self::assertInstanceOf(WeldingWorkflow::class, $captured);
        self::assertSame($template, $captured->getTemplate());
        self::assertSame(WeldingWorkflowStatusEnum::Draft, $captured->getStatus());
        self::assertNotNull($captured->getReference());
        self::assertStringStartsWith('WLD-', $captured->getReference());
        self::assertSame($captured, $result);
    }

    public function testCreateThrowsWhenTemplateNotFound(): void
    {
        $this->templateRepository->method('find')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->manager->create(new WeldingWorkflowInput(templateId: 999));
    }

    public function testCreateResolvesAssignee(): void
    {
        $template = new WeldingWorkflowTemplate();
        $this->templateRepository->method('find')->willReturn($template);

        $employee = new Employee();
        $this->employeeRepository->expects(self::once())->method('find')->with(7)->willReturn($employee);

        $captured = null;
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof WeldingWorkflow) {
                    $captured = $entity;
                }
            }
        );

        $this->manager->create(new WeldingWorkflowInput(templateId: 1, assigneeId: 7));

        self::assertSame($employee, $captured->getAssignee());
    }

    public function testStartFailsIfNotInDraft(): void
    {
        $workflow = new WeldingWorkflow();
        $workflow->setStatus(WeldingWorkflowStatusEnum::InProgress);

        $this->expectException(RuntimeException::class);
        $this->manager->start($workflow);
    }

    public function testStartFailsIfNoTemplate(): void
    {
        $workflow = new WeldingWorkflow();
        $workflow->setStatus(WeldingWorkflowStatusEnum::Draft);

        $this->expectException(RuntimeException::class);
        $this->manager->start($workflow);
    }

    public function testStartSnapshotsStepsAndTransitionsToInProgress(): void
    {
        $template = new WeldingWorkflowTemplate();
        $stepTpl = new WeldingWorkflowStepTemplate();
        $stepTpl->setTitle('S1')->setPosition(0);
        $this->setProperty($template, 'steps', new ArrayCollection([$stepTpl]));

        $workflow = new WeldingWorkflow();
        $workflow->setStatus(WeldingWorkflowStatusEnum::Draft);
        $workflow->setTemplate($template);

        $persistedSteps = [];
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$persistedSteps): void {
                if ($entity instanceof WeldingWorkflowStep) {
                    $persistedSteps[] = $entity;
                }
            }
        );

        $this->manager->start($workflow);

        self::assertSame(WeldingWorkflowStatusEnum::InProgress, $workflow->getStatus());
        self::assertNotNull($workflow->getStartedAt());
        self::assertCount(1, $persistedSteps);
        self::assertSame($stepTpl, $persistedSteps[0]->getStepTemplate());
    }

    public function testRejectFailsIfTerminal(): void
    {
        $workflow = new WeldingWorkflow();
        $workflow->setStatus(WeldingWorkflowStatusEnum::Completed);

        $this->expectException(RuntimeException::class);
        $this->manager->reject($workflow, 'foo');
    }

    public function testRejectSetsStatusReasonAndTimestamp(): void
    {
        $workflow = new WeldingWorkflow();
        $workflow->setStatus(WeldingWorkflowStatusEnum::InProgress);

        $this->manager->reject($workflow, 'non-conformity');

        self::assertSame(WeldingWorkflowStatusEnum::Rejected, $workflow->getStatus());
        self::assertSame('non-conformity', $workflow->getRejectionReason());
        self::assertNotNull($workflow->getRejectedAt());
    }

    public function testArchiveOnlyAllowedFromCompleted(): void
    {
        $workflow = new WeldingWorkflow();
        $workflow->setStatus(WeldingWorkflowStatusEnum::InProgress);

        $this->expectException(RuntimeException::class);
        $this->manager->archive($workflow);
    }

    public function testArchiveTransitionsCompletedToArchived(): void
    {
        $workflow = new WeldingWorkflow();
        $workflow->setStatus(WeldingWorkflowStatusEnum::Completed);

        $this->manager->archive($workflow);

        self::assertSame(WeldingWorkflowStatusEnum::Archived, $workflow->getStatus());
    }

    public function testStartDelegatesTaskSnapshotPerStep(): void
    {
        $template = new WeldingWorkflowTemplate();

        $stepTpl = new WeldingWorkflowStepTemplate();
        $stepTpl->setTitle('S1');
        $taskTpl = new WeldingWorkflowStepTaskTemplate();
        $taskTpl->setLabel('Check gauge')->setRequired(true);
        $this->setProperty($stepTpl, 'tasks', new ArrayCollection([$taskTpl]));

        $this->setProperty($template, 'steps', new ArrayCollection([$stepTpl]));

        $workflow = new WeldingWorkflow();
        $workflow->setStatus(WeldingWorkflowStatusEnum::Draft);
        $workflow->setTemplate($template);

        $capturedTemplates = null;
        $this->taskManager
            ->expects(self::once())
            ->method('snapshotFromTemplates')
            ->willReturnCallback(static function ($step, iterable $templates) use (&$capturedTemplates): array {
                $capturedTemplates = iterator_to_array($templates instanceof IteratorAggregate ? $templates->getIterator() : $templates);

                return [new WeldingWorkflowStepTask()];
            });

        $this->manager->start($workflow);

        self::assertNotNull($capturedTemplates);
        self::assertCount(1, $capturedTemplates);
        self::assertSame('Check gauge', $capturedTemplates[0]->getLabel());
    }

    private function setProperty(object $target, string $property, mixed $value): void
    {
        $rp = new ReflectionProperty($target::class, $property);
        $rp->setValue($target, $value);
    }
}
