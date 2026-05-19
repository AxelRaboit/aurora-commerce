<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Media\Library\Repository\MediaRepository;
use Aurora\Module\PdfForm\Enum\PdfTemplateStatusEnum;
use Aurora\Module\PdfForm\PdfTemplate\Dto\PdfTemplateInput;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplate;
use Aurora\Module\PdfForm\PdfTemplate\Manager\PdfTemplateManager;
use Aurora\Module\PdfForm\Service\PdfManipulatorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class PdfTemplateManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private PdfTemplateManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $connection = $this->createStub(Connection::class);

        $this->manager = new PdfTemplateManager(
            $this->entityManager,
            $this->createStub(MediaRepository::class),
            $this->createStub(PdfManipulatorInterface::class),
            new AuditLogger(
                $this->entityManager,
                $security,
                new SequenceGenerator($connection),
                $this->createStub(SettingRepository::class),
            ),
            '/tmp',
        );
    }

    /** Capture le PdfTemplate passé à persist() en ignorant les autres entités (ex. AuditLog). */
    private function captureTemplate(mixed &$captured): void
    {
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof PdfTemplate) {
                    $captured = $entity;
                }
            }
        );
    }

    public function testCreateSetsRequiresSignatureTrue(): void
    {
        $captured = null;
        $this->captureTemplate($captured);

        $this->manager->create(new PdfTemplateInput(name: 'Test', requiresSignature: true));

        self::assertInstanceOf(PdfTemplate::class, $captured);
        self::assertTrue($captured->isRequiresSignature());
    }

    public function testCreateRequiresSignatureDefaultsFalse(): void
    {
        $captured = null;
        $this->captureTemplate($captured);

        $this->manager->create(new PdfTemplateInput(name: 'Test'));

        self::assertInstanceOf(PdfTemplate::class, $captured);
        self::assertFalse($captured->isRequiresSignature());
    }

    public function testCreateSetsFlattenOnGenerate(): void
    {
        $captured = null;
        $this->captureTemplate($captured);

        $this->manager->create(new PdfTemplateInput(name: 'Test', flattenOnGenerate: true));

        self::assertInstanceOf(PdfTemplate::class, $captured);
        self::assertTrue($captured->isFlattenOnGenerate());
    }

    public function testCreateSetsNameAndStatus(): void
    {
        $captured = null;
        $this->captureTemplate($captured);

        $this->manager->create(new PdfTemplateInput(
            name: 'Mon template',
            status: PdfTemplateStatusEnum::Active,
        ));

        self::assertInstanceOf(PdfTemplate::class, $captured);
        self::assertSame('Mon template', $captured->getName());
        self::assertSame(PdfTemplateStatusEnum::Active, $captured->getStatus());
    }

    public function testUpdateAppliesRequiresSignature(): void
    {
        $template = new PdfTemplate();
        $template->setName('Old');

        // AuditLogger appelle aussi flush() — on accepte plusieurs appels.
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->update($template, new PdfTemplateInput(name: 'Old', requiresSignature: true));

        self::assertTrue($template->isRequiresSignature());
    }

    public function testUpdateResetsRequiresSignatureToFalse(): void
    {
        $template = new PdfTemplate();
        $template->setName('Old')->setRequiresSignature(true);

        $this->manager->update($template, new PdfTemplateInput(name: 'Old', requiresSignature: false));

        self::assertFalse($template->isRequiresSignature());
    }

    public function testDeleteCallsRemoveAndFlush(): void
    {
        $template = new PdfTemplate();
        $template->setName('ToDelete');

        $this->entityManager->expects(self::once())->method('remove')->with($template);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($template);
    }
}
