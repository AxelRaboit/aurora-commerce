<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Setting\Command;

use Aurora\Module\Configuration\Setting\Command\ApplicationParameterCommand;
use Aurora\Module\Configuration\Setting\Entity\Setting;
use Aurora\Module\Configuration\Setting\Entity\SettingInterface;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;
use Aurora\Module\Configuration\Setting\Provider\ApplicationParameterProviderInterface;
use Aurora\Module\Configuration\Setting\Provider\CoreApplicationParameterProvider;
use Aurora\Module\Configuration\Setting\Provider\CoreModuleParameterProvider;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tiny enum used to simulate a client-side custom enum that gets
 * exposed via a custom ApplicationParameterProviderInterface.
 */
enum FakeClientSettingEnum: string implements ApplicationParameterEnumInterface
{
    case SomeClientKey = 'backend_client_custom_setting';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return 'fake.label';
    }

    public function getDescription(): string
    {
        return 'fake description';
    }

    public function getDefaultValue(): string
    {
        return '';
    }

    public function getType(): string
    {
        return 'string';
    }

    public function getGroup(): string
    {
        return 'fake_group';
    }
}

#[AllowMockObjectsWithoutExpectations]
final class ApplicationParameterCommandTest extends TestCase
{
    /** @param iterable<ApplicationParameterProviderInterface>|null $providers */
    private function makeTester(SettingRepository $repository, EntityManagerInterface $em, ?iterable $providers = null): CommandTester
    {
        $providers ??= [new CoreApplicationParameterProvider(), new CoreModuleParameterProvider()];
        $command = new ApplicationParameterCommand($repository, $em, $providers);

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

    public function testCustomProviderContributesItsEnumCases(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $repository->method('findAll')->willReturn([]);

        // Custom enum + provider exposed by the client project.
        $customProvider = new class implements ApplicationParameterProviderInterface {
            public function getParameters(): iterable
            {
                yield FakeClientSettingEnum::SomeClientKey;
            }
        };

        $tester = $this->makeTester($repository, $em, [$customProvider]);
        $tester->execute(['--dry-run' => true]);

        $display = $tester->getDisplay();
        // The custom key appears in the "to-create" list
        self::assertStringContainsString(FakeClientSettingEnum::SomeClientKey->getKey(), $display);
    }

    public function testClientSettingKeptWhenItsProviderIsRegistered(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        // Existing client-side setting already in DB (admin saved a value)
        $clientSetting = $this->makeSettingStub(FakeClientSettingEnum::SomeClientKey->getKey());
        $repository->method('findAll')->willReturn([$clientSetting]);

        // EM should NOT remove the client setting — the provider claims its key
        $em->expects(self::never())->method('remove');

        $customProvider = new class implements ApplicationParameterProviderInterface {
            public function getParameters(): iterable
            {
                yield FakeClientSettingEnum::SomeClientKey;
            }
        };

        $tester = $this->makeTester($repository, $em, [$customProvider]);
        $tester->execute([]);

        $display = $tester->getDisplay();
        // No "obsolète" line for our custom key
        self::assertStringNotContainsString(FakeClientSettingEnum::SomeClientKey->getKey().' (obsolète)', $display);
    }
}
