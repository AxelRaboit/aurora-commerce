<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Welding\Enum\WorkflowStatusEnum;
use Aurora\Module\Welding\Enum\WorkflowStepStatusEnum;
use Aurora\Module\Welding\Service\WeldingStepNotifier;
use Aurora\Module\Welding\Workflow\Entity\Workflow;
use Aurora\Module\Welding\WorkflowStep\Dto\WorkflowStepValidationInput;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStep;
use Aurora\Module\Welding\WorkflowStep\Manager\WorkflowStepManager;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplate;
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
final class WorkflowStepManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private WeldingStepNotifier $notifier;
    private WorkflowStepManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->notifier = $this->createMock(WeldingStepNotifier::class);
        $this->manager = $this->makeManager();
    }

    private function makeManager(): WorkflowStepManager
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $dbalResult = $this->createStub(Result::class);
        $dbalResult->method('fetchOne')->willReturn(1);
        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($dbalResult);

        return new WorkflowStepManager(
            $this->entityManager,
            new AuditLogger(
                $this->entityManager,
                $security,
                new SequenceGenerator($connection),
                $this->createStub(SettingRepository::class),
            ),
            $this->notifier,
        );
    }

    private function makeWelder(int $id = 1): User
    {
        $user = new User();
        $rp = new ReflectionProperty(User::class, 'id');
        $rp->setValue($user, $id);

        return $user;
    }

    private function makeStep(WorkflowStepStatusEnum $status, bool $requiresValidation, ?Workflow $workflow = null): WorkflowStep
    {
        $tpl = new WorkflowStepTemplate();
        $tpl->setTitle('S')->setRequiresValidation($requiresValidation);

        $step = new WorkflowStep();
        $step->setStatus($status);
        $step->setStepTemplate($tpl);

        $workflow ??= new Workflow();
        $workflow->setStatus(WorkflowStatusEnum::InProgress);
        $rp = new ReflectionProperty(Workflow::class, 'steps');
        $rp->setValue($workflow, new ArrayCollection([$step]));
        $step->setWorkflow($workflow);

        return $step;
    }

    public function testSubmitFromPendingNonValidatingGoesDirectlyToValidated(): void
    {
        $step = $this->makeStep(WorkflowStepStatusEnum::Pending, requiresValidation: false);
        $welder = $this->makeWelder();

        $this->notifier->expects(self::never())->method('notifyAwaitingValidation');

        $this->manager->submit($step, $welder);

        self::assertSame(WorkflowStepStatusEnum::Validated, $step->getStatus());
        self::assertSame($welder, $step->getCompletedBy());
        self::assertSame($welder, $step->getValidatedBy());
        self::assertNotNull($step->getCompletedAt());
        self::assertNotNull($step->getValidatedAt());
    }

    public function testSubmitFromPendingValidatingGoesToAwaitingValidation(): void
    {
        $step = $this->makeStep(WorkflowStepStatusEnum::Pending, requiresValidation: true);
        $welder = $this->makeWelder();

        $this->notifier->expects(self::once())->method('notifyAwaitingValidation')->with($step);

        $this->manager->submit($step, $welder);

        self::assertSame(WorkflowStepStatusEnum::AwaitingValidation, $step->getStatus());
        self::assertSame($welder, $step->getCompletedBy());
        self::assertNull($step->getValidatedBy());
    }

    public function testSubmitFromTerminalThrows(): void
    {
        $step = $this->makeStep(WorkflowStepStatusEnum::Validated, requiresValidation: false);
        $this->expectException(RuntimeException::class);
        $this->manager->submit($step, $this->makeWelder());
    }

    public function testRecordValidationApprovesAndAutoCompletesWorkflow(): void
    {
        $step = $this->makeStep(WorkflowStepStatusEnum::AwaitingValidation, requiresValidation: true);
        $validator = $this->makeWelder(99);

        $this->manager->recordValidation($step, $validator, new WorkflowStepValidationInput(decision: 'validate', comment: 'OK'));

        self::assertSame(WorkflowStepStatusEnum::Validated, $step->getStatus());
        self::assertSame($validator, $step->getValidatedBy());
        self::assertSame('OK', $step->getValidationComment());
        // Workflow only has this 1 step, all validated → auto Completed
        self::assertSame(WorkflowStatusEnum::Completed, $step->getWorkflow()->getStatus());
        self::assertNotNull($step->getWorkflow()->getCompletedAt());
    }

    public function testRecordValidationRejectsAndResetsStepToPending(): void
    {
        $step = $this->makeStep(WorkflowStepStatusEnum::AwaitingValidation, requiresValidation: true);
        $welder = $this->makeWelder(1);
        $step->setCompletedBy($welder)->setValidationComment('previous');

        $validator = $this->makeWelder(99);

        $this->manager->recordValidation($step, $validator, new WorkflowStepValidationInput(decision: 'reject', comment: 'redo'));

        self::assertSame(WorkflowStepStatusEnum::Pending, $step->getStatus());
        self::assertSame('redo', $step->getRejectionComment());
        self::assertNull($step->getCompletedBy());
        self::assertNull($step->getValidatedBy());
        self::assertNull($step->getValidationComment());
    }

    public function testRecordValidationFailsIfNotAwaiting(): void
    {
        $step = $this->makeStep(WorkflowStepStatusEnum::Pending, requiresValidation: true);
        $this->expectException(RuntimeException::class);
        $this->manager->recordValidation($step, $this->makeWelder(99), new WorkflowStepValidationInput(decision: 'validate'));
    }
}
