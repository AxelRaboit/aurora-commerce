<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\Service;

use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Welding\Service\WeldingStepNotifier;
use Aurora\Module\Welding\Workflow\Entity\Workflow;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStep;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

#[AllowMockObjectsWithoutExpectations]
final class WeldingStepNotifierTest extends TestCase
{
    public function testNoOpWhenRecipientEmpty(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $settings = $this->createStub(SettingRepository::class);
        $settings->method('getOrDefault')->willReturn('');

        $notifier = new WeldingStepNotifier(
            $mailer,
            $this->createStub(TwigEnvironment::class),
            $this->createStub(UrlGeneratorInterface::class),
            $settings,
            $this->createStub(TranslatorInterface::class),
            'noreply@example.com',
        );

        $step = new WorkflowStep();
        $step->setWorkflow(new Workflow());

        $notifier->notifyAwaitingValidation($step);
    }

    public function testSilentlySwallowsMailerExceptionsToNotBlockState(): void
    {
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willThrowException(new \RuntimeException('SMTP down'));

        $settings = $this->createStub(SettingRepository::class);
        $settings->method('getOrDefault')->willReturnCallback(
            static fn ($key) => 'inspector@example.com',
        );

        $twig = $this->createStub(TwigEnvironment::class);
        $twig->method('render')->willReturn('<html></html>');

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://example.com/runner/1');

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('[Site] WLD-1 — step');

        $notifier = new WeldingStepNotifier(
            $mailer,
            $twig,
            $urlGenerator,
            $settings,
            $translator,
            'noreply@example.com',
        );

        $step = new WorkflowStep();
        $step->setWorkflow(new Workflow());

        // Should not throw
        $notifier->notifyAwaitingValidation($step);

        self::assertTrue(true);
    }

    public function testNoOpWhenStepHasNoWorkflow(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $settings = $this->createStub(SettingRepository::class);
        $settings->method('getOrDefault')->willReturn('inspector@example.com');

        $notifier = new WeldingStepNotifier(
            $mailer,
            $this->createStub(TwigEnvironment::class),
            $this->createStub(UrlGeneratorInterface::class),
            $settings,
            $this->createStub(TranslatorInterface::class),
            'noreply@example.com',
        );

        $step = new WorkflowStep(); // no workflow

        $notifier->notifyAwaitingValidation($step);
    }
}
