<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\Enum\WeldingValidatorRoleEnum;
use Aurora\Module\Welding\WorkflowStepTemplate\Dto\WeldingWorkflowStepTemplateInput;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplate;
use Aurora\Module\Welding\WorkflowStepTemplate\Manager\WeldingWorkflowStepTemplateManager;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WeldingWorkflowStepTemplateRepository;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplate;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WeldingWorkflowTemplateRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class WeldingWorkflowStepTemplateManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private WeldingWorkflowTemplateRepository $workflowTemplateRepository;
    private WeldingWorkflowStepTemplateRepository $stepRepository;
    private WeldingWorkflowStepTemplateManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->workflowTemplateRepository = $this->createMock(WeldingWorkflowTemplateRepository::class);
        $this->stepRepository = $this->createMock(WeldingWorkflowStepTemplateRepository::class);
        $this->manager = $this->makeManager();
    }

    private function makeManager(): WeldingWorkflowStepTemplateManager
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $dbalResult = $this->createStub(Result::class);
        $dbalResult->method('fetchOne')->willReturn(1);

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($dbalResult);

        return new WeldingWorkflowStepTemplateManager(
            $this->entityManager,
            $this->workflowTemplateRepository,
            $this->stepRepository,
            new AuditLogger(
                $this->entityManager,
                $security,
                new SequenceGenerator($connection),
                $this->createStub(SettingRepository::class),
            ),
        );
    }

    public function testCreateResolvesWorkflowTemplateAndAppliesFields(): void
    {
        $parent = new WeldingWorkflowTemplate();
        $parent->setTitle('Parent');

        $this->workflowTemplateRepository->expects(self::once())->method('find')->with(42)->willReturn($parent);

        $captured = null;
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof WeldingWorkflowStepTemplate) {
                    $captured = $entity;
                }
            }
        );

        $this->manager->create(new WeldingWorkflowStepTemplateInput(
            workflowTemplateId: 42,
            position: 2,
            title: 'Étape 1',
            description: 'Préparation',
            requiresValidation: true,
            validatorRole: WeldingValidatorRoleEnum::Inspector,
        ));

        self::assertInstanceOf(WeldingWorkflowStepTemplate::class, $captured);
        self::assertSame($parent, $captured->getWorkflowTemplate());
        self::assertSame(2, $captured->getPosition());
        self::assertSame('Étape 1', $captured->getTitle());
        self::assertSame('Préparation', $captured->getDescription());
        self::assertTrue($captured->getRequiresValidation());
        self::assertSame(WeldingValidatorRoleEnum::Inspector, $captured->getValidatorRole());
    }

    public function testCreateThrowsWhenWorkflowTemplateNotFound(): void
    {
        $this->workflowTemplateRepository->method('find')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('WeldingWorkflowTemplate #999 not found');

        $this->manager->create(new WeldingWorkflowStepTemplateInput(workflowTemplateId: 999, title: 'X'));
    }

    public function testApplyInputClearsValidatorRoleWhenRequiresValidationIsFalse(): void
    {
        $parent = new WeldingWorkflowTemplate();
        $this->workflowTemplateRepository->method('find')->willReturn($parent);

        $captured = null;
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof WeldingWorkflowStepTemplate) {
                    $captured = $entity;
                }
            }
        );

        $this->manager->create(new WeldingWorkflowStepTemplateInput(
            workflowTemplateId: 1,
            title: 'Step',
            requiresValidation: false,
            validatorRole: WeldingValidatorRoleEnum::Inspector, // should be cleared
        ));

        self::assertNull($captured->getValidatorRole());
    }
}
