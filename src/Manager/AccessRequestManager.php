<?php

declare(strict_types=1);

namespace App\Manager;

use App\Contract\AccessRequestManagerInterface;
use App\Entity\AccessRequest;
use App\Enum\AccessRequestStatusEnum;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment as TwigEnvironment;

#[AsAlias(AccessRequestManagerInterface::class)]
final readonly class AccessRequestManager implements AccessRequestManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private TwigEnvironment $twig,
        private UrlGeneratorInterface $urlGenerator,
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

    private function sendAdminNotification(AccessRequest $request): void
    {
        $body = $this->twig->render('email/access_request_admin.html.twig', [
            'request' => $request,
        ]);

        $this->mailer->send((new Email())
            ->from($this->mailerFrom)
            ->to($this->adminEmail)
            ->subject(sprintf("Demande d'accès de %s", $request->getRequesterName() ?? $request->getRequesterEmail()))
            ->html($body));
    }

    private function sendRequesterApproval(AccessRequest $request, ?string $generatedPassword = null): void
    {
        $loginUrl = $this->urlGenerator->generate('admin_login', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $body = $this->twig->render('email/access_request_approved.html.twig', [
            'request' => $request,
            'loginUrl' => $loginUrl,
            'generatedPassword' => $generatedPassword,
        ]);

        $this->mailer->send((new Email())
            ->from($this->mailerFrom)
            ->to($request->getRequesterEmail())
            ->subject("Votre demande d'accès a été approuvée")
            ->html($body));
    }

    private function sendRequesterRejection(AccessRequest $request): void
    {
        $body = $this->twig->render('email/access_request_rejected.html.twig', [
            'request' => $request,
        ]);

        $this->mailer->send((new Email())
            ->from($this->mailerFrom)
            ->to($request->getRequesterEmail())
            ->subject("Votre demande d'accès")
            ->html($body));
    }
}
