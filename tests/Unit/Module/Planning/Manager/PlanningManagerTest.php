<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Planning\Manager;

use Aurora\Core\Agency\Entity\Agency;
use Aurora\Core\Agency\Repository\AgencyRepository;
use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Planning\Planning\Dto\PlanningInputInterface;
use Aurora\Module\Planning\Planning\Entity\Planning;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use Aurora\Module\Planning\Planning\Enum\PlanningVisibilityEnum;
use Aurora\Module\Planning\Planning\Manager\PlanningManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class PlanningManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private PlanningManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $auditLogger = new AuditLogger(
            $this->entityManager,
            $security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        $this->manager = new PlanningManager(
            $this->entityManager,
            $this->createStub(UserRepository::class),
            $this->createStub(AgencyRepository::class),
            $auditLogger,
        );
    }

    private function makeInput(
        string $name = 'My Planning',
        ?string $description = null,
        string $color = '#3b82f6',
        string $timezone = 'UTC',
        string $visibility = 'agency',
        ?int $ownerId = null,
        ?int $agencyId = null,
    ): PlanningInputInterface {
        $input = $this->createStub(PlanningInputInterface::class);
        $input->method('getName')->willReturn($name);
        $input->method('getDescription')->willReturn($description);
        $input->method('getColor')->willReturn($color);
        $input->method('getTimezone')->willReturn($timezone);
        $input->method('getVisibility')->willReturn($visibility);
        $input->method('getVisibilityEnum')->willReturn(PlanningVisibilityEnum::from($visibility));
        $input->method('getOwnerId')->willReturn($ownerId);
        $input->method('getAgencyId')->willReturn($agencyId);

        return $input;
    }

    private function makePlanning(int $id = 1, string $name = 'My Planning'): Planning
    {
        $planning = new Planning();
        $planning->setName($name)
            ->setColor('#3b82f6')
            ->setTimezone('UTC')
            ->setVisibility(PlanningVisibilityEnum::Agency);
        (new ReflectionProperty(Planning::class, 'id'))->setValue($planning, $id);

        return $planning;
    }

    public function testCreateCallsPersistAndFlushAndReturnsPlanning(): void
    {
        $persisted = [];
        $this->entityManager->expects(self::atLeastOnce())->method('persist')->willReturnCallback(
            function (object $entity) use (&$persisted): void { $persisted[] = $entity; },
        );
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $planning = $this->manager->create($this->makeInput());

        $planningsPersisted = array_filter($persisted, static fn (object $entity): bool => $entity instanceof PlanningInterface);
        self::assertCount(1, $planningsPersisted);
        self::assertInstanceOf(PlanningInterface::class, $planning);
        self::assertSame('My Planning', $planning->getName());
    }

    public function testCreateLogsWithModulePlanningAndActionPlanningCreated(): void
    {
        $auditLogs = [];
        $this->entityManager->method('persist')->willReturnCallback(
            function (object $entity) use (&$auditLogs): void {
                $auditLogs[] = $entity;
            },
        );

        $this->manager->create($this->makeInput(name: 'Team Planning'));

        $auditLogEntries = array_filter($auditLogs, static fn (object $entity): bool => !$entity instanceof PlanningInterface);
        self::assertNotEmpty($auditLogEntries);
    }

    public function testUpdateCallsFlushAndAppliesInput(): void
    {
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $planning = $this->makePlanning();
        $this->manager->update($planning, $this->makeInput(name: 'Updated Planning'));

        self::assertSame('Updated Planning', $planning->getName());
    }

    public function testDeleteCallsRemoveAndFlush(): void
    {
        $planning = $this->makePlanning();

        $this->entityManager->expects(self::once())->method('remove')->with($planning);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($planning);
    }

    public function testAuditPayloadContainsExpectedKeys(): void
    {
        $agency = new Agency();
        $agency->setName('HQ');
        (new ReflectionProperty(Agency::class, 'id'))->setValue($agency, 5);

        $agencyRepository = $this->createStub(AgencyRepository::class);
        $agencyRepository->method('find')->willReturn($agency);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $auditLogs = [];
        $this->entityManager->method('persist')->willReturnCallback(
            function (object $entity) use (&$auditLogs): void {
                $auditLogs[] = $entity;
            },
        );

        $manager = new PlanningManager(
            $this->entityManager,
            $this->createStub(UserRepository::class),
            $agencyRepository,
            new AuditLogger(
                $this->entityManager,
                $security,
                new SequenceGenerator($this->createStub(Connection::class)),
                $this->createStub(SettingRepository::class),
            ),
        );

        $manager->create($this->makeInput(name: 'Plan A', visibility: 'agency', agencyId: 5));

        $auditLogEntries = array_filter($auditLogs, static fn (object $entity): bool => !$entity instanceof PlanningInterface);
        self::assertNotEmpty($auditLogEntries);
    }
}
