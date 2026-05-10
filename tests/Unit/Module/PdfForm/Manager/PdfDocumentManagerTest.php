<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\PdfForm\Enum\PdfDocumentStatusEnum;
use Aurora\Module\PdfForm\PdfDocument\Dto\PdfDocumentInput;
use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocument;
use Aurora\Module\PdfForm\PdfDocument\Manager\PdfDocumentManager;
use Aurora\Module\PdfForm\PdfDocument\Service\PdfDocumentStorage;
use Aurora\Module\PdfForm\PdfTemplate\Repository\PdfTemplateRepository;
use Aurora\Module\PdfForm\Service\PdfManipulatorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class PdfDocumentManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private PdfManipulatorInterface $pdfManipulator;
    private PdfDocumentManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->pdfManipulator = $this->createStub(PdfManipulatorInterface::class);
        $this->pdfManipulator->method('isAvailable')->willReturn(false);

        $this->manager = $this->makeManager();
    }

    private function makeManager(?PdfDocumentStorage $storage = null): PdfDocumentManager
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $dbalResult = $this->createStub(Result::class);
        $dbalResult->method('fetchOne')->willReturn(1);

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($dbalResult);

        return new PdfDocumentManager(
            $this->entityManager,
            $this->createStub(PdfTemplateRepository::class),
            $this->pdfManipulator,
            $storage ?? new PdfDocumentStorage('/tmp'),
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

    /** Capture le PdfDocument passé à persist() en ignorant les autres entités (ex. AuditLog). */
    private function captureDocument(mixed &$captured): void
    {
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof PdfDocument) {
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

        $this->manager->generate(new PdfDocumentInput(templateId: 0, fieldValues: $fieldValues));

        self::assertInstanceOf(PdfDocument::class, $captured);
        self::assertSame($fieldValues, $captured->getFieldValues());
    }

    public function testGeneratePreservesSignatureKey(): void
    {
        $signatureDataUrl = 'data:image/png;base64,iVBORw0KGgo=';
        $fieldValues = ['nom' => 'Durand', '__signature__' => $signatureDataUrl];
        $captured = null;
        $this->captureDocument($captured);

        $this->manager->generate(new PdfDocumentInput(templateId: 0, fieldValues: $fieldValues));

        self::assertInstanceOf(PdfDocument::class, $captured);
        self::assertSame($signatureDataUrl, $captured->getFieldValues()['__signature__']);
    }

    public function testGenerateStatusIsDraftWhenManipulatorUnavailable(): void
    {
        $captured = null;
        $this->captureDocument($captured);

        $this->manager->generate(new PdfDocumentInput(templateId: 0, fieldValues: []));

        self::assertInstanceOf(PdfDocument::class, $captured);
        self::assertSame(PdfDocumentStatusEnum::Draft, $captured->getStatus());
    }

    public function testGenerateSetsLabel(): void
    {
        $captured = null;
        $this->captureDocument($captured);

        $this->manager->generate(new PdfDocumentInput(templateId: 0, label: 'Contrat Dupont', fieldValues: []));

        self::assertInstanceOf(PdfDocument::class, $captured);
        self::assertSame('Contrat Dupont', $captured->getLabel());
    }

    public function testDeleteCallsRemoveAndFlush(): void
    {
        $document = new PdfDocument();

        $this->entityManager->expects(self::once())->method('remove')->with($document);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($document);
    }
}
