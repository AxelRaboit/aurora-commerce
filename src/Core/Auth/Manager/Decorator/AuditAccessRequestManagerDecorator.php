<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Manager\Decorator;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Auth\Entity\AccessRequest;
use Aurora\Core\Auth\Entity\AccessRequestInterface;
use Aurora\Core\Auth\Manager\AccessRequestManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(decorates: AccessRequestManagerInterface::class)]
final readonly class AuditAccessRequestManagerDecorator implements AccessRequestManagerInterface
{
    public function __construct(
        #[AutowireDecorated]
        private AccessRequestManagerInterface $inner,
        private AuditLogger $auditLogger,
    ) {}

    public function create(string $email, ?string $name, ?string $message): AccessRequestInterface
    {
        $request = $this->inner->create($email, $name, $message);
        $this->auditLogger->log('core', 'access_request.created', 'AccessRequest', $request->getId(), ['email' => $email]);

        return $request;
    }

    public function approve(AccessRequest $request, ?string $generatedPassword = null): void
    {
        $this->inner->approve($request, $generatedPassword);
        $this->auditLogger->log('core', 'access_request.approved', 'AccessRequest', $request->getId(), ['email' => $request->getRequesterEmail()]);
    }

    public function reject(AccessRequest $request): void
    {
        $this->inner->reject($request);
        $this->auditLogger->log('core', 'access_request.rejected', 'AccessRequest', $request->getId(), ['email' => $request->getRequesterEmail()]);
    }
}
