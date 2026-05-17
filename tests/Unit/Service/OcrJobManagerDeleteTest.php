<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Service;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Media\Library\Entity\Media;
use Aurora\Module\Media\Library\Manager\MediaManagerInterface;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Manager\TiersManagerInterface;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use Aurora\Module\Billing\Ocr\Manager\OcrJobManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Messenger\MessageBusInterface;

#[AllowMockObjectsWithoutExpectations]
final class OcrJobManagerDeleteTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private MediaManagerInterface&MockObject $mediaManager;
    private TiersManagerInterface&MockObject $tiersManager;
    private InvoiceRepository&MockObject $invoiceRepository;
    private AuditLogger&MockObject $auditLogger;
    private OcrJobManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->mediaManager = $this->createMock(MediaManagerInterface::class);
        $this->tiersManager = $this->createMock(TiersManagerInterface::class);
        $this->invoiceRepository = $this->createMock(InvoiceRepository::class);
        $this->auditLogger = $this->createMock(AuditLogger::class);

        $this->manager = new OcrJobManager(
            $this->em,
            $this->mediaManager,
            $this->createMock(MessageBusInterface::class),
            $this->auditLogger,
            new SequenceGenerator($this->createMock(Connection::class)),
            $this->createMock(SettingRepository::class),
            $this->invoiceRepository,
            $this->tiersManager,
        );
    }

    private function makeJob(): OcrJob
    {
        $media = $this->createStub(Media::class);

        $job = new OcrJob();
        $job->setMedia($media);
        $job->setStatus(OcrJobStatusEnum::Completed);
        (new ReflectionProperty(OcrJob::class, 'id'))->setValue($job, 42);

        return $job;
    }

    private function makeInvoice(InvoiceStatusEnum $status, ?Tiers $tiers = null): Invoice
    {
        $invoice = new Invoice();
        $invoice->setStatus($status);
        if ($tiers instanceof Tiers) {
            $invoice->setTiers($tiers);
        }
        (new ReflectionProperty(Invoice::class, 'id'))->setValue($invoice, 99);

        return $invoice;
    }

    private function makeTiers(): Tiers
    {
        $tiers = new Tiers();
        $tiers->setName('Acme Corp');
        (new ReflectionProperty(Tiers::class, 'id'))->setValue($tiers, 7);

        return $tiers;
    }

    public function testDeleteJobOnlyWhenNoLinkedInvoice(): void
    {
        $job = $this->makeJob();

        $this->invoiceRepository->method('findOneBy')->willReturn(null);

        $this->em->expects($this->once())->method('remove')->with($job);
        $this->em->expects($this->once())->method('flush');
        $this->mediaManager->expects($this->once())->method('delete')->with($job->getMedia());
        $this->tiersManager->expects($this->never())->method('delete');

        $this->manager->delete($job);
    }

    public function testDeleteJobAndLinkedDraftInvoice(): void
    {
        $job = $this->makeJob();
        $invoice = $this->makeInvoice(InvoiceStatusEnum::Draft);

        $this->invoiceRepository->method('findOneBy')->willReturn($invoice);

        $removeCalls = [];
        $this->em->method('remove')->willReturnCallback(function (object $entity) use (&$removeCalls) {
            $removeCalls[] = $entity;
        });
        $this->em->expects($this->exactly(2))->method('flush');
        $this->mediaManager->expects($this->once())->method('delete');
        $this->tiersManager->expects($this->never())->method('delete');

        $this->manager->delete($job);

        $this->assertContains($invoice, $removeCalls);
        $this->assertContains($job, $removeCalls);
    }

    public function testSkipsInvoiceDeletionWhenNotDeletable(): void
    {
        $job = $this->makeJob();
        $invoice = $this->makeInvoice(InvoiceStatusEnum::Validated);

        $this->invoiceRepository->method('findOneBy')->willReturn($invoice);

        $this->em->expects($this->once())->method('remove')->with($job);
        $this->em->expects($this->once())->method('flush');
        $this->mediaManager->expects($this->once())->method('delete');

        $this->manager->delete($job);
    }

    public function testDeletesTiersWhenFlagSetAndInvoiceDeletable(): void
    {
        $tiers = $this->makeTiers();
        $job = $this->makeJob();
        $invoice = $this->makeInvoice(InvoiceStatusEnum::Draft, $tiers);

        $this->invoiceRepository->method('findOneBy')->willReturn($invoice);

        $this->tiersManager->expects($this->once())->method('delete')->with($tiers);

        $this->manager->delete($job, deleteTiers: true);
    }

    public function testDoesNotDeleteTiersWhenFlagFalse(): void
    {
        $tiers = $this->makeTiers();
        $job = $this->makeJob();
        $invoice = $this->makeInvoice(InvoiceStatusEnum::Draft, $tiers);

        $this->invoiceRepository->method('findOneBy')->willReturn($invoice);

        $this->tiersManager->expects($this->never())->method('delete');

        $this->manager->delete($job, deleteTiers: false);
    }

    public function testAuditLogsInvoiceAndJobDeletion(): void
    {
        $job = $this->makeJob();
        $invoice = $this->makeInvoice(InvoiceStatusEnum::Draft);

        $this->invoiceRepository->method('findOneBy')->willReturn($invoice);

        $loggedEvents = [];
        $this->auditLogger->method('log')->willReturnCallback(
            function (string $module, string $event) use (&$loggedEvents) {
                $loggedEvents[] = $event;
            }
        );

        $this->manager->delete($job);

        $this->assertContains('invoice.deleted', $loggedEvents);
        $this->assertContains('ocr.job.deleted', $loggedEvents);
    }

    public function testAuditLogsOnlyJobWhenNoInvoice(): void
    {
        $job = $this->makeJob();

        $this->invoiceRepository->method('findOneBy')->willReturn(null);

        $loggedEvents = [];
        $this->auditLogger->method('log')->willReturnCallback(
            function (string $module, string $event) use (&$loggedEvents) {
                $loggedEvents[] = $event;
            }
        );

        $this->manager->delete($job);

        $this->assertNotContains('invoice.deleted', $loggedEvents);
        $this->assertContains('ocr.job.deleted', $loggedEvents);
    }
}
