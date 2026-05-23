<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplate;
use Aurora\Module\PdfForm\PdfTemplate\Repository\PdfTemplateRepository;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Dto\WorkflowStepPdfTemplateInput;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WorkflowStepPdfTemplate;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Manager\WorkflowStepPdfTemplateManager;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplate;
use Aurora\Module\Welding\WorkflowStepTemplate\Repository\WorkflowStepTemplateRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class WorkflowStepPdfTemplateManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private WorkflowStepTemplateRepository $stepRepository;
    private PdfTemplateRepository $pdfTemplateRepository;
    private WorkflowStepPdfTemplateManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->stepRepository = $this->createMock(WorkflowStepTemplateRepository::class);
        $this->pdfTemplateRepository = $this->createMock(PdfTemplateRepository::class);
        $this->manager = $this->makeManager();
    }

    private function makeManager(): WorkflowStepPdfTemplateManager
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $dbalResult = $this->createStub(Result::class);
        $dbalResult->method('fetchOne')->willReturn(1);

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($dbalResult);

        return new WorkflowStepPdfTemplateManager(
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
        $step = new WorkflowStepTemplate();
        $pdf = new PdfTemplate();
        $this->stepRepository->method('find')->willReturn($step);
        $this->pdfTemplateRepository->method('find')->willReturn($pdf);

        $captured = null;
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof WorkflowStepPdfTemplate) {
                    $captured = $entity;
                }
            }
        );

        $this->manager->create(new WorkflowStepPdfTemplateInput(
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
        $this->pdfTemplateRepository->method('find')->willReturn(new PdfTemplate());

        $this->expectException(RuntimeException::class);
        $this->manager->create(new WorkflowStepPdfTemplateInput(workflowStepTemplateId: 999, pdfTemplateId: 1));
    }

    public function testCreateThrowsWhenPdfTemplateNotFound(): void
    {
        $this->stepRepository->method('find')->willReturn(new WorkflowStepTemplate());
        $this->pdfTemplateRepository->method('find')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->manager->create(new WorkflowStepPdfTemplateInput(workflowStepTemplateId: 1, pdfTemplateId: 999));
    }
}
