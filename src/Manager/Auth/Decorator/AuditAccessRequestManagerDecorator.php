<?php

declare(strict_types=1);

namespace App\Manager\Auth\Decorator;

use App\Contract\Auth\AccessRequestManagerInterface;
use App\Entity\AccessRequest;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(decorates: AccessRequestManagerInterface::class)]
final readonly class AuditAccessRequestManagerDecorator implements AccessRequestManagerInterface
{
    public function __construct(
        #[AutowireDecorated]
        private AccessRequestManagerInterface $inner,
        private LoggerInterface $logger,
        private Security $security,
    ) {}

    public function create(string $email, ?string $name, ?string $message): AccessRequest
    {
        $request = $this->inner->create($email, $name, $message);

        $this->logger->info('access_request.created', [
            'requestId' => $request->getId(),
            'email' => $email,
        ]);

        return $request;
    }

    public function approve(AccessRequest $request, ?string $generatedPassword = null): void
    {
        $this->inner->approve($request, $generatedPassword);

        $this->logger->info('access_request.approved', [
            'requestId' => $request->getId(),
            'email' => $request->getRequesterEmail(),
            'actor' => $this->actorEmail(),
        ]);
    }

    public function reject(AccessRequest $request): void
    {
        $this->inner->reject($request);

        $this->logger->info('access_request.rejected', [
            'requestId' => $request->getId(),
            'email' => $request->getRequesterEmail(),
            'actor' => $this->actorEmail(),
        ]);
    }

    private function actorEmail(): ?string
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user->getEmail() : null;
    }
}
