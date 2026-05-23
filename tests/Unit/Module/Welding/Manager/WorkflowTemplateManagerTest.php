<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Welding\Enum\WeldingWorkflowTemplateStatusEnum;
use Aurora\Module\Welding\WorkflowTemplate\Dto\WeldingWorkflowTemplateInput;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplate;
use Aurora\Module\Welding\WorkflowTemplate\Manager\WeldingWorkflowTemplateManager;
use Aurora\Module\Welding\WorkflowTemplate\Repository\WeldingWorkflowTemplateRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class WeldingWorkflowTemplateManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private WeldingWorkflowTemplateManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->manager = $this->makeManager();
    }

    private function makeManager(): WeldingWorkflowTemplateManager
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $dbalResult = $this->createStub(Result::class);
        $dbalResult->method('fetchOne')->willReturn(1);

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($dbalResult);

        return new WeldingWorkflowTemplateManager(
            $this->entityManager,
            $this->createStub(WeldingWorkflowTemplateRepository::class),
            new AuditLogger(
                $this->entityManager,
                $security,
                new SequenceGenerator($connection),
                $this->createStub(SettingRepository::class),
            ),
        );
    }

    private function captureTemplate(mixed &$captured): void
    {
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$captured): void {
                if ($entity instanceof WeldingWorkflowTemplate) {
                    $captured = $entity;
                }
            }
        );
    }

    public function testCreateStartsInDraftStatusWithVersion1(): void
    {
        $captured = null;
        $this->captureTemplate($captured);

        $result = $this->manager->create(new WeldingWorkflowTemplateInput(title: 'DMOS TIG-001', description: 'Procédure soudure TIG', applicableTo: 'TIG'));

        self::assertInstanceOf(WeldingWorkflowTemplate::class, $captured);
        self::assertSame('DMOS TIG-001', $captured->getTitle());
        self::assertSame(1, $captured->getVersion());
        self::assertSame(WeldingWorkflowTemplateStatusEnum::Draft, $captured->getStatus());
        self::assertSame($captured, $result);
    }

    public function testCreateAcceptsNullableFields(): void
    {
        $captured = null;
        $this->captureTemplate($captured);

        $this->manager->create(new WeldingWorkflowTemplateInput(title: 'Minimal'));

        self::assertInstanceOf(WeldingWorkflowTemplate::class, $captured);
        self::assertNull($captured->getDescription());
        self::assertNull($captured->getApplicableTo());
    }

    public function testPublishTransitionsStatus(): void
    {
        $template = new WeldingWorkflowTemplate();
        $template->setTitle('A')->setStatus(WeldingWorkflowTemplateStatusEnum::Draft);

        $this->manager->publish($template);

        self::assertSame(WeldingWorkflowTemplateStatusEnum::Published, $template->getStatus());
    }

    public function testArchiveTransitionsStatus(): void
    {
        $template = new WeldingWorkflowTemplate();
        $template->setTitle('A')->setStatus(WeldingWorkflowTemplateStatusEnum::Published);

        $this->manager->archive($template);

        self::assertSame(WeldingWorkflowTemplateStatusEnum::Archived, $template->getStatus());
    }

    public function testCloneAsNewVersionBumpsVersionAndLinksParent(): void
    {
        $source = new WeldingWorkflowTemplate();
        $source->setTitle('Original')->setVersion(3)->setStatus(WeldingWorkflowTemplateStatusEnum::Published)->setApplicableTo('TIG')->setDescription('Desc');

        $captured = null;
        $this->captureTemplate($captured);

        $result = $this->manager->cloneAsNewVersion($source);

        self::assertInstanceOf(WeldingWorkflowTemplate::class, $captured);
        self::assertSame($captured, $result);
        self::assertSame('Original', $captured->getTitle());
        self::assertSame('TIG', $captured->getApplicableTo());
        self::assertSame('Desc', $captured->getDescription());
        self::assertSame(4, $captured->getVersion());
        self::assertSame(WeldingWorkflowTemplateStatusEnum::Draft, $captured->getStatus());
        self::assertSame($source, $captured->getParentVersion());
    }

    public function testUpdateAppliesInputFields(): void
    {
        $template = new WeldingWorkflowTemplate();
        $template->setTitle('Old')->setStatus(WeldingWorkflowTemplateStatusEnum::Draft);

        $this->manager->update($template, new WeldingWorkflowTemplateInput(title: 'New', description: 'd', applicableTo: 'MIG'));

        self::assertSame('New', $template->getTitle());
        self::assertSame('d', $template->getDescription());
        self::assertSame('MIG', $template->getApplicableTo());
    }
}
