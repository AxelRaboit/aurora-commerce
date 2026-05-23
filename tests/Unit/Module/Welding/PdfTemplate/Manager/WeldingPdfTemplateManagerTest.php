<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\PdfTemplate\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Media\Library\Repository\MediaRepository;
use Aurora\Module\Welding\Enum\WeldingPdfTemplateStatusEnum;
use Aurora\Module\Welding\PdfTemplate\Dto\WeldingPdfTemplateInput;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplate;
use Aurora\Module\Welding\PdfTemplate\Manager\WeldingPdfTemplateManager;
use Aurora\Module\Welding\Service\WeldingPdfManipulatorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class WeldingPdfTemplateManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private WeldingPdfTemplateManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $connection = $this->createStub(Connection::class);

        $this->manager = new WeldingPdfTemplateManager(
            $this->entityManager,
            $this->createStub(MediaRepository::class),
            $this->createStub(WeldingPdfManipulatorInterface::class),
            new AuditLogger(
                $this->entityManager,
                $security,
                new SequenceGenerator($connection),
                $this->createStub(SettingRepository::class),
            ),
            '/tmp',
        );
    }

    /** Capture le WeldingPdfTemplate passé à persist() en ignorant les autres entités (ex. AuditLog). */
    private function captureTemplate(mixed &$captured): void
    {
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof WeldingPdfTemplate) {
                    $captured = $entity;
                }
            }
        );
    }

    public function testCreateSetsRequiresSignatureTrue(): void
    {
        $captured = null;
        $this->captureTemplate($captured);

        $this->manager->create(new WeldingPdfTemplateInput(name: 'Test', requiresSignature: true));

        self::assertInstanceOf(WeldingPdfTemplate::class, $captured);
        self::assertTrue($captured->isRequiresSignature());
    }

    public function testCreateRequiresSignatureDefaultsFalse(): void
    {
        $captured = null;
        $this->captureTemplate($captured);

        $this->manager->create(new WeldingPdfTemplateInput(name: 'Test'));

        self::assertInstanceOf(WeldingPdfTemplate::class, $captured);
        self::assertFalse($captured->isRequiresSignature());
    }

    public function testCreateSetsFlattenOnGenerate(): void
    {
        $captured = null;
        $this->captureTemplate($captured);

        $this->manager->create(new WeldingPdfTemplateInput(name: 'Test', flattenOnGenerate: true));

        self::assertInstanceOf(WeldingPdfTemplate::class, $captured);
        self::assertTrue($captured->isFlattenOnGenerate());
    }

    public function testCreateSetsNameAndStatus(): void
    {
        $captured = null;
        $this->captureTemplate($captured);

        $this->manager->create(new WeldingPdfTemplateInput(
            name: 'Mon template',
            status: WeldingPdfTemplateStatusEnum::Active,
        ));

        self::assertInstanceOf(WeldingPdfTemplate::class, $captured);
        self::assertSame('Mon template', $captured->getName());
        self::assertSame(WeldingPdfTemplateStatusEnum::Active, $captured->getStatus());
    }

    public function testUpdateAppliesRequiresSignature(): void
    {
        $template = new WeldingPdfTemplate();
        $template->setName('Old');

        // AuditLogger appelle aussi flush() — on accepte plusieurs appels.
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->update($template, new WeldingPdfTemplateInput(name: 'Old', requiresSignature: true));

        self::assertTrue($template->isRequiresSignature());
    }

    public function testUpdateResetsRequiresSignatureToFalse(): void
    {
        $template = new WeldingPdfTemplate();
        $template->setName('Old')->setRequiresSignature(true);

        $this->manager->update($template, new WeldingPdfTemplateInput(name: 'Old', requiresSignature: false));

        self::assertFalse($template->isRequiresSignature());
    }

    public function testDeleteCallsRemoveAndFlush(): void
    {
        $template = new WeldingPdfTemplate();
        $template->setName('ToDelete');

        $this->entityManager->expects(self::once())->method('remove')->with($template);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($template);
    }
}
