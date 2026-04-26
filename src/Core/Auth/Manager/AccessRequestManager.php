<?php

declare(strict_types=1);

namespace App\Core\Auth\Manager;

use App\Core\Auth\Contract\AccessRequestManagerInterface;
use App\Core\Auth\Entity\AccessRequest;
use App\Core\Auth\Enum\AccessRequestStatusEnum;
use App\Core\Setting\Enum\ApplicationParameterEnum;
use App\Core\Setting\Repository\SettingRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

#[AsAlias(AccessRequestManagerInterface::class)]
final readonly class AccessRequestManager implements AccessRequestManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private TwigEnvironment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private SettingRepository $settingRepository,
        private TranslatorInterface $translator,
        private string $adminEmail,
        private string $mailerFrom,
    ) {}

    public function create(string $email, ?string $name, ?string $message): AccessRequest
    {
        $request = new AccessRequest($email, new DateTimeImmutable('+48 hours'));
        $request->setRequesterName($name ?: null);
        $request->setMessage($message ?: null);

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        $this->sendAdminNotification($request);

        return $request;
    }

    public function approve(AccessRequest $request, ?string $generatedPassword = null): void
    {
        $request->setStatus(AccessRequestStatusEnum::Approved);
        $this->entityManager->flush();

        $this->sendRequesterApproval($request, $generatedPassword);
    }

    public function reject(AccessRequest $request): void
    {
        $request->setStatus(AccessRequestStatusEnum::Rejected);
        $this->entityManager->flush();

        $this->sendRequesterRejection($request);
    }

    private function siteName(): string
    {
        return $this->settingRepository->getOrDefault(ApplicationParameterEnum::SiteName);
    }

    private function sendAdminNotification(AccessRequest $request): void
    {
        $siteName = $this->siteName();
        $adminUrl = $this->urlGenerator->generate('dev_access_requests', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $body = $this->twig->render('@Shared/email/access_request_admin.html.twig', [
            'request' => $request,
            'siteName' => $siteName,
            'adminUrl' => $adminUrl,
        ]);

        $subject = $this->translator->trans('shared.mail.access_request_admin.heading');

        $this->mailer->send((new Email())
            ->from($this->mailerFrom)
            ->to($this->adminEmail)
            ->subject(sprintf('[%s] %s', $siteName, $subject))
            ->html($body));
    }

    private function sendRequesterApproval(AccessRequest $request, ?string $generatedPassword = null): void
    {
        $siteName = $this->siteName();
        $loginUrl = $this->urlGenerator->generate('admin_login', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $body = $this->twig->render('@Shared/email/access_request_approved.html.twig', [
            'request' => $request,
            'loginUrl' => $loginUrl,
            'generatedPassword' => $generatedPassword,
            'siteName' => $siteName,
        ]);

        $subject = $this->translator->trans('shared.mail.access_request_approved.heading');

        $this->mailer->send((new Email())
            ->from($this->mailerFrom)
            ->to($request->getRequesterEmail())
            ->subject(sprintf('[%s] %s', $siteName, $subject))
            ->html($body));
    }

    private function sendRequesterRejection(AccessRequest $request): void
    {
        $siteName = $this->siteName();

        $body = $this->twig->render('@Shared/email/access_request_rejected.html.twig', [
            'request' => $request,
            'siteName' => $siteName,
        ]);

        $subject = $this->translator->trans('shared.mail.access_request_rejected.heading');

        $this->mailer->send((new Email())
            ->from($this->mailerFrom)
            ->to($request->getRequesterEmail())
            ->subject(sprintf('[%s] %s', $siteName, $subject))
            ->html($body));
    }
}
