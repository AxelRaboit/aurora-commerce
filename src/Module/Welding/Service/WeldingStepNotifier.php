<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Service;

use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Welding\Setting\WeldingSettingEnum;
use Aurora\Module\Welding\Workflow\Entity\WorkflowInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStepInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;
use Twig\Environment as TwigEnvironment;

/**
 * Sends an email to the configured Welding notification recipient when a
 * step transitions to AwaitingValidation. V1 single recipient — V2 will
 * route by `validatorRole` (one address per Inspector/QA/Supervisor/Customer).
 *
 * Empty `WeldingSettingEnum::NotificationEmail` ⇒ no email sent (graceful).
 * Any mail-send failure is swallowed: the workflow state change must not
 * be blocked by a transient SMTP error. Failures are auditable via the
 * domain audit logger (separate from this notifier).
 */
final readonly class WeldingStepNotifier
{
    public function __construct(
        private MailerInterface $mailer,
        private TwigEnvironment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private SettingRepository $settingRepository,
        #[Autowire('%app.mailer_from%')]
        private string $mailerFrom,
    ) {}

    public function notifyAwaitingValidation(WorkflowStepInterface $step): void
    {
        $recipient = mb_trim($this->settingRepository->getOrDefault(WeldingSettingEnum::NotificationEmail));
        if ('' === $recipient) {
            return;
        }

        $workflow = $step->getWorkflow();
        if (null === $workflow) {
            return;
        }

        try {
            $runnerUrl = $this->urlGenerator->generate(
                'backend_welding_workflows_runner',
                ['id' => $workflow->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
        } catch (Throwable) {
            $runnerUrl = null;
        }

        $siteName = $this->settingRepository->getOrDefault(ApplicationParameterEnum::SiteName);

        try {
            $body = $this->twig->render('@Welding/email/step_awaiting_validation.html.twig', [
                'workflow' => $workflow,
                'step' => $step,
                'runnerUrl' => $runnerUrl,
                'siteName' => $siteName,
            ]);

            $email = (new Email())
                ->from($this->mailerFrom)
                ->to($recipient)
                ->subject($this->buildSubject($workflow, $step, $siteName))
                ->html($body);

            $this->mailer->send($email);
        } catch (Throwable) {
            // Notification failure is non-fatal — the welder/validator can still
            // see the AwaitingValidation state in the runner and act on it.
        }
    }

    private function buildSubject(WorkflowInterface $workflow, WorkflowStepInterface $step, string $siteName): string
    {
        $ref = $workflow->getReference() ?? '#'.($workflow->getId() ?? '?');
        $title = $step->getStepTemplate()?->getTitle() ?? '';

        return sprintf('[%s] %s — étape à valider : %s', $siteName, $ref, $title);
    }
}
