<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Hr\Employee\Entity\Employee;
use Aurora\Module\Hr\Employee\Repository\EmployeeRepository;
use Aurora\Module\Welding\Enum\WorkflowStatusEnum;
use Aurora\Module\Welding\Workflow\Dto\WorkflowInput;
use Aurora\Module\Welding\Workflow\Entity\Workflow;
use Aurora\Module\Welding\Workflow\Manager\WorkflowManager;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplate;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WorkflowTemplate;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WorkflowTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class WorkflowManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private WorkflowTemplateRepository $templateRepository;
    private EmployeeRepository $employeeRepository;
    private SettingRepository $settingRepository;
    private WorkflowManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->templateRepository = $this->createMock(WorkflowTemplateRepository::class);
        $this->employeeRepository = $this->createMock(EmployeeRepository::class);
        $this->settingRepository = $this->createMock(SettingRepository::class);
        $this->settingRepository->method('getOrDefault')->willReturn('WLD');
        $this->manager = $this->makeManager();
    }

    private function makeManager(): WorkflowManager
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $dbalResult = $this->createStub(Result::class);
        $dbalResult->method('fetchOne')->willReturn(42);

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($dbalResult);

        return new WorkflowManager(
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
        );
    }

    public function testCreateGeneratesReferenceAndStartsInDraft(): void
    {
        $template = new WorkflowTemplate();
        $template->setTitle('DMOS');
        $this->templateRepository->method('find')->willReturn($template);

        $captured = null;
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof Workflow) {
                    $captured = $entity;
                }
            }
        );

        $result = $this->manager->create(new WorkflowInput(templateId: 1));

        self::assertInstanceOf(Workflow::class, $captured);
        self::assertSame($template, $captured->getTemplate());
        self::assertSame(WorkflowStatusEnum::Draft, $captured->getStatus());
        self::assertNotNull($captured->getReference());
        self::assertStringStartsWith('WLD-', $captured->getReference());
        self::assertSame($captured, $result);
    }

    public function testCreateThrowsWhenTemplateNotFound(): void
    {
        $this->templateRepository->method('find')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->manager->create(new WorkflowInput(templateId: 999));
    }

    public function testCreateResolvesAssignee(): void
    {
        $template = new WorkflowTemplate();
        $this->templateRepository->method('find')->willReturn($template);

        $employee = new Employee();
        $this->employeeRepository->expects(self::once())->method('find')->with(7)->willReturn($employee);

        $captured = null;
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof Workflow) {
                    $captured = $entity;
                }
            }
        );

        $this->manager->create(new WorkflowInput(templateId: 1, assigneeId: 7));

        self::assertSame($employee, $captured->getAssignee());
    }

    public function testStartFailsIfNotInDraft(): void
    {
        $workflow = new Workflow();
        $workflow->setStatus(WorkflowStatusEnum::InProgress);

        $this->expectException(RuntimeException::class);
        $this->manager->start($workflow);
    }

    public function testStartFailsIfNoTemplate(): void
    {
        $workflow = new Workflow();
        $workflow->setStatus(WorkflowStatusEnum::Draft);

        $this->expectException(RuntimeException::class);
        $this->manager->start($workflow);
    }

    public function testStartSnapshotsStepsAndTransitionsToInProgress(): void
    {
        $template = new WorkflowTemplate();
        $stepTpl = new WorkflowStepTemplate();
        $stepTpl->setTitle('S1')->setPosition(0);
        $this->setProperty($template, 'steps', new ArrayCollection([$stepTpl]));

        $workflow = new Workflow();
        $workflow->setStatus(WorkflowStatusEnum::Draft);
        $workflow->setTemplate($template);

        $persistedSteps = [];
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$persistedSteps): void {
                if ($entity instanceof \Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStep) {
                    $persistedSteps[] = $entity;
                }
            }
        );

        $this->manager->start($workflow);

        self::assertSame(WorkflowStatusEnum::InProgress, $workflow->getStatus());
        self::assertNotNull($workflow->getStartedAt());
        self::assertCount(1, $persistedSteps);
        self::assertSame($stepTpl, $persistedSteps[0]->getStepTemplate());
    }

    public function testRejectFailsIfTerminal(): void
    {
        $workflow = new Workflow();
        $workflow->setStatus(WorkflowStatusEnum::Completed);

        $this->expectException(RuntimeException::class);
        $this->manager->reject($workflow, 'foo');
    }

    public function testRejectSetsStatusReasonAndTimestamp(): void
    {
        $workflow = new Workflow();
        $workflow->setStatus(WorkflowStatusEnum::InProgress);

        $this->manager->reject($workflow, 'non-conformity');

        self::assertSame(WorkflowStatusEnum::Rejected, $workflow->getStatus());
        self::assertSame('non-conformity', $workflow->getRejectionReason());
        self::assertNotNull($workflow->getRejectedAt());
    }

    public function testArchiveOnlyAllowedFromCompleted(): void
    {
        $workflow = new Workflow();
        $workflow->setStatus(WorkflowStatusEnum::InProgress);

        $this->expectException(RuntimeException::class);
        $this->manager->archive($workflow);
    }

    public function testArchiveTransitionsCompletedToArchived(): void
    {
        $workflow = new Workflow();
        $workflow->setStatus(WorkflowStatusEnum::Completed);

        $this->manager->archive($workflow);

        self::assertSame(WorkflowStatusEnum::Archived, $workflow->getStatus());
    }

    private function setProperty(object $target, string $property, mixed $value): void
    {
        $rp = new ReflectionProperty($target::class, $property);
        $rp->setValue($target, $value);
    }
}
