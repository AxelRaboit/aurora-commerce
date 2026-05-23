<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplate;
use Aurora\Module\Welding\PdfTemplate\Repository\WeldingPdfTemplateRepository;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Dto\WeldingWorkflowStepPdfTemplateInput;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WeldingWorkflowStepPdfTemplate;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Manager\WeldingWorkflowStepPdfTemplateManager;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplate;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WeldingWorkflowStepTemplateRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class WeldingWorkflowStepPdfTemplateManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private WeldingWorkflowStepTemplateRepository $stepRepository;
    private WeldingPdfTemplateRepository $pdfTemplateRepository;
    private WeldingWorkflowStepPdfTemplateManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->stepRepository = $this->createMock(WeldingWorkflowStepTemplateRepository::class);
        $this->pdfTemplateRepository = $this->createMock(WeldingPdfTemplateRepository::class);
        $this->manager = $this->makeManager();
    }

    private function makeManager(): WeldingWorkflowStepPdfTemplateManager
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $dbalResult = $this->createStub(Result::class);
        $dbalResult->method('fetchOne')->willReturn(1);

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($dbalResult);

        return new WeldingWorkflowStepPdfTemplateManager(
            $this->entityManager,
            $this->stepRepository,
            $this->pdfTemplateRepository,
            new AuditLogger(
                $this->entityManager,
                $security,
                new SequenceGenerator($connection),
                $this->createStub(SettingRepository::class),
            ),
        );
    }

    public function testCreateResolvesBothForeignKeys(): void
    {
        $step = new WeldingWorkflowStepTemplate();
        $pdf = new WeldingPdfTemplate();
        $this->stepRepository->method('find')->willReturn($step);
        $this->pdfTemplateRepository->method('find')->willReturn($pdf);

        $captured = null;
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof WeldingWorkflowStepPdfTemplate) {
                    $captured = $entity;
                }
            }
        );

        $this->manager->create(new WeldingWorkflowStepPdfTemplateInput(
            workflowStepTemplateId: 1,
            pdfTemplateId: 2,
            position: 3,
            required: false,
        ));

        self::assertSame($step, $captured->getWorkflowStepTemplate());
        self::assertSame($pdf, $captured->getPdfTemplate());
        self::assertSame(3, $captured->getPosition());
        self::assertFalse($captured->getRequired());
    }

    public function testCreateThrowsWhenStepNotFound(): void
    {
        $this->stepRepository->method('find')->willReturn(null);
        $this->pdfTemplateRepository->method('find')->willReturn(new WeldingPdfTemplate());

        $this->expectException(RuntimeException::class);
        $this->manager->create(new WeldingWorkflowStepPdfTemplateInput(workflowStepTemplateId: 999, pdfTemplateId: 1));
    }

    public function testCreateThrowsWhenPdfTemplateNotFound(): void
    {
        $this->stepRepository->method('find')->willReturn(new WeldingWorkflowStepTemplate());
        $this->pdfTemplateRepository->method('find')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->manager->create(new WeldingWorkflowStepPdfTemplateInput(workflowStepTemplateId: 1, pdfTemplateId: 999));
    }
}
