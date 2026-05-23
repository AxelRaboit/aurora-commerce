<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\WorkflowStep\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Welding\Enum\WeldingWorkflowStatusEnum;
use Aurora\Module\Welding\Enum\WeldingWorkflowStepStatusEnum;
use Aurora\Module\Welding\Service\WeldingStepNotifier;
use Aurora\Module\Welding\Workflow\Entity\WeldingWorkflow;
use Aurora\Module\Welding\WorkflowStep\Dto\WeldingWorkflowStepValidationInput;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStep;
use Aurora\Module\Welding\WorkflowStep\Manager\WeldingWorkflowStepManager;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplate;
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
final class WeldingWorkflowStepManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private WeldingStepNotifier $notifier;
    private WeldingWorkflowStepManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->notifier = $this->createMock(WeldingStepNotifier::class);
        $this->manager = $this->makeManager();
    }

    private function makeManager(): WeldingWorkflowStepManager
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $dbalResult = $this->createStub(Result::class);
        $dbalResult->method('fetchOne')->willReturn(1);
        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($dbalResult);

        return new WeldingWorkflowStepManager(
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

    private function makeStep(WeldingWorkflowStepStatusEnum $status, bool $requiresValidation, ?WeldingWorkflow $workflow = null): WeldingWorkflowStep
    {
        $tpl = new WeldingWorkflowStepTemplate();
        $tpl->setTitle('S')->setRequiresValidation($requiresValidation);

        $step = new WeldingWorkflowStep();
        $step->setStatus($status);
        $step->setStepTemplate($tpl);

        $workflow ??= new WeldingWorkflow();
        $workflow->setStatus(WeldingWorkflowStatusEnum::InProgress);
        $rp = new ReflectionProperty(WeldingWorkflow::class, 'steps');
        $rp->setValue($workflow, new ArrayCollection([$step]));
        $step->setWorkflow($workflow);

        return $step;
    }

    public function testSubmitFromPendingNonValidatingGoesDirectlyToValidated(): void
    {
        $step = $this->makeStep(WeldingWorkflowStepStatusEnum::Pending, requiresValidation: false);
        $welder = $this->makeWelder();

        $this->notifier->expects(self::never())->method('notifyAwaitingValidation');

        $this->manager->submit($step, $welder);

        self::assertSame(WeldingWorkflowStepStatusEnum::Validated, $step->getStatus());
        self::assertSame($welder, $step->getCompletedBy());
        self::assertSame($welder, $step->getValidatedBy());
        self::assertNotNull($step->getCompletedAt());
        self::assertNotNull($step->getValidatedAt());
    }

    public function testSubmitFromPendingValidatingGoesToAwaitingValidation(): void
    {
        $step = $this->makeStep(WeldingWorkflowStepStatusEnum::Pending, requiresValidation: true);
        $welder = $this->makeWelder();

        $this->notifier->expects(self::once())->method('notifyAwaitingValidation')->with($step);

        $this->manager->submit($step, $welder);

        self::assertSame(WeldingWorkflowStepStatusEnum::AwaitingValidation, $step->getStatus());
        self::assertSame($welder, $step->getCompletedBy());
        self::assertNull($step->getValidatedBy());
    }

    public function testSubmitFromTerminalThrows(): void
    {
        $step = $this->makeStep(WeldingWorkflowStepStatusEnum::Validated, requiresValidation: false);
        $this->expectException(RuntimeException::class);
        $this->manager->submit($step, $this->makeWelder());
    }

    public function testRecordValidationApprovesAndAutoCompletesWorkflow(): void
    {
        $step = $this->makeStep(WeldingWorkflowStepStatusEnum::AwaitingValidation, requiresValidation: true);
        $validator = $this->makeWelder(99);

        $this->manager->recordValidation($step, $validator, new WeldingWorkflowStepValidationInput(decision: 'validate', comment: 'OK'));

        self::assertSame(WeldingWorkflowStepStatusEnum::Validated, $step->getStatus());
        self::assertSame($validator, $step->getValidatedBy());
        self::assertSame('OK', $step->getValidationComment());
        // WeldingWorkflow only has this 1 step, all validated → auto Completed
        self::assertSame(WeldingWorkflowStatusEnum::Completed, $step->getWorkflow()->getStatus());
        self::assertNotNull($step->getWorkflow()->getCompletedAt());
    }

    public function testRecordValidationRejectsAndResetsStepToPending(): void
    {
        $step = $this->makeStep(WeldingWorkflowStepStatusEnum::AwaitingValidation, requiresValidation: true);
        $welder = $this->makeWelder(1);
        $step->setCompletedBy($welder)->setValidationComment('previous');

        $validator = $this->makeWelder(99);

        $this->manager->recordValidation($step, $validator, new WeldingWorkflowStepValidationInput(decision: 'reject', comment: 'redo'));

        self::assertSame(WeldingWorkflowStepStatusEnum::Pending, $step->getStatus());
        self::assertSame('redo', $step->getRejectionComment());
        self::assertNull($step->getCompletedBy());
        self::assertNull($step->getValidatedBy());
        self::assertNull($step->getValidationComment());
    }

    public function testRecordValidationFailsIfNotAwaiting(): void
    {
        $step = $this->makeStep(WeldingWorkflowStepStatusEnum::Pending, requiresValidation: true);
        $this->expectException(RuntimeException::class);
        $this->manager->recordValidation($step, $this->makeWelder(99), new WeldingWorkflowStepValidationInput(decision: 'validate'));
    }
}
