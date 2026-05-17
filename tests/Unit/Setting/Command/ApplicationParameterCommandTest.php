<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Setting\Command;

use Aurora\Module\Configuration\Setting\Command\ApplicationParameterCommand;
use Aurora\Module\Configuration\Setting\Entity\Setting;
use Aurora\Module\Configuration\Setting\Entity\SettingInterface;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[AllowMockObjectsWithoutExpectations]
final class ApplicationParameterCommandTest extends TestCase
{
    private function makeTester(SettingRepository $repository, EntityManagerInterface $em): CommandTester
    {
        $command = new ApplicationParameterCommand($repository, $em);

        return new CommandTester($command);
    }

    private function makeSettingStub(string $key, string $description = 'desc', string $type = 'string', ?string $group = null): SettingInterface
    {
        $setting = new Setting($key, 'value', $description, $type, $group);

        return $setting;
    }

    public function testCreatesAbsentParameters(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $repository->method('findAll')->willReturn([]);
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::once())->method('flush');

        $tester = $this->makeTester($repository, $em);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('créé(s)', $tester->getDisplay());
    }

    public function testDryRunDoesNotFlush(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $repository->method('findAll')->willReturn([]);
        $em->expects(self::never())->method('flush');
        $em->expects(self::never())->method('persist');

        $tester = $this->makeTester($repository, $em);
        $tester->execute(['--dry-run' => true]);

        self::assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        self::assertStringContainsString('dry-run', $display);
        self::assertStringContainsString('créé(s)', $display);
    }

    public function testSyncsOutdatedDescription(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $staleSetting = $this->makeSettingStub('site_name', 'old_description', 'string', 'application');

        $repository->method('findAll')->willReturn([$staleSetting]);
        $em->expects(self::once())->method('flush');

        $tester = $this->makeTester($repository, $em);
        $tester->execute([]);

        $display = $tester->getDisplay();
        self::assertStringContainsString('mis à jour', $display);
    }

    public function testDeletesObsoleteParameters(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $obsolete = $this->makeSettingStub('totally_unknown_obsolete_key');

        $repository->method('findAll')->willReturn([$obsolete]);
        $em->expects(self::once())->method('remove')->with($obsolete);
        $em->expects(self::once())->method('flush');

        $tester = $this->makeTester($repository, $em);
        $tester->execute([]);

        $display = $tester->getDisplay();
        self::assertStringContainsString('supprimé(s)', $display);
        self::assertStringContainsString('totally_unknown_obsolete_key', $display);
    }

    public function testSummaryContainsAllCounters(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $repository->method('findAll')->willReturn([]);
        $em->method('flush');

        $tester = $this->makeTester($repository, $em);
        $tester->execute([]);

        $display = $tester->getDisplay();
        self::assertMatchesRegularExpression('/\d+ créé\(s\), \d+ mis à jour, \d+ supprimé\(s\)/', $display);
    }

    public function testDryRunDisplaysPendingCreations(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $repository->method('findAll')->willReturn([]);

        $tester = $this->makeTester($repository, $em);
        $tester->execute(['--dry-run' => true]);

        $display = $tester->getDisplay();
        self::assertStringContainsString('+', $display);
    }
}
