<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\PdfForm\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\Enum\WeldingPdfDocumentStatusEnum;
use Aurora\Module\Welding\PdfDocument\Dto\WeldingPdfDocumentInput;
use Aurora\Module\Welding\PdfDocument\Entity\WeldingPdfDocument;
use Aurora\Module\Welding\PdfDocument\Manager\WeldingPdfDocumentManager;
use Aurora\Module\Welding\PdfDocument\Service\WeldingPdfDocumentStorage;
use Aurora\Module\Welding\PdfTemplate\Repository\WeldingPdfTemplateRepository;
use Aurora\Module\Welding\Service\WeldingPdfManipulatorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class WeldingPdfDocumentManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private WeldingPdfManipulatorInterface $pdfManipulator;
    private WeldingPdfDocumentManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->pdfManipulator = $this->createStub(WeldingPdfManipulatorInterface::class);
        $this->pdfManipulator->method('isAvailable')->willReturn(false);

        $this->manager = $this->makeManager();
    }

    private function makeManager(?WeldingPdfDocumentStorage $storage = null): WeldingPdfDocumentManager
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $dbalResult = $this->createStub(Result::class);
        $dbalResult->method('fetchOne')->willReturn(1);

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($dbalResult);

        return new WeldingPdfDocumentManager(
            $this->entityManager,
            $this->createStub(WeldingPdfTemplateRepository::class),
            $this->pdfManipulator,
            $storage ?? new WeldingPdfDocumentStorage('/tmp'),
            $this->createStub(SettingRepository::class),
            new SequenceGenerator($connection),
            new AuditLogger(
                $this->entityManager,
                $security,
                new SequenceGenerator($connection),
                $this->createStub(SettingRepository::class),
            ),
            '/tmp',
        );
    }

    /** Capture le WeldingPdfDocument passé à persist() en ignorant les autres entités (ex. AuditLog). */
    private function captureDocument(mixed &$captured): void
    {
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof WeldingPdfDocument) {
                    $captured = $entity;
                }
            }
        );
    }

    public function testGenerateStoresFieldValuesAsIs(): void
    {
        $fieldValues = ['nom' => 'Dupont', 'ville' => 'Paris'];
        $captured = null;
        $this->captureDocument($captured);

        $this->manager->generate(new WeldingPdfDocumentInput(templateId: 0, fieldValues: $fieldValues));

        self::assertInstanceOf(WeldingPdfDocument::class, $captured);
        self::assertSame($fieldValues, $captured->getFieldValues());
    }

    public function testGeneratePreservesSignatureKey(): void
    {
        $signatureDataUrl = 'data:image/png;base64,iVBORw0KGgo=';
        $fieldValues = ['nom' => 'Durand', '__signature__' => $signatureDataUrl];
        $captured = null;
        $this->captureDocument($captured);

        $this->manager->generate(new WeldingPdfDocumentInput(templateId: 0, fieldValues: $fieldValues));

        self::assertInstanceOf(WeldingPdfDocument::class, $captured);
        self::assertSame($signatureDataUrl, $captured->getFieldValues()['__signature__']);
    }

    public function testGenerateStatusIsDraftWhenManipulatorUnavailable(): void
    {
        $captured = null;
        $this->captureDocument($captured);

        $this->manager->generate(new WeldingPdfDocumentInput(templateId: 0, fieldValues: []));

        self::assertInstanceOf(WeldingPdfDocument::class, $captured);
        self::assertSame(WeldingPdfDocumentStatusEnum::Draft, $captured->getStatus());
    }

    public function testGenerateSetsLabel(): void
    {
        $captured = null;
        $this->captureDocument($captured);

        $this->manager->generate(new WeldingPdfDocumentInput(templateId: 0, label: 'Contrat Dupont', fieldValues: []));

        self::assertInstanceOf(WeldingPdfDocument::class, $captured);
        self::assertSame('Contrat Dupont', $captured->getLabel());
    }

    public function testDeleteCallsRemoveAndFlush(): void
    {
        $document = new WeldingPdfDocument();

        $this->entityManager->expects(self::once())->method('remove')->with($document);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($document);
    }
}
