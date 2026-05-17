<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Service\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Platform\Service\Dto\ServiceInputInterface;
use Aurora\Core\Platform\Service\Entity\Service;
use Aurora\Core\Platform\Service\Entity\ServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ServiceManagerInterface::class)]
class ServiceManager implements ServiceManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(ServiceInputInterface $input): ServiceInterface
    {
        $service = $this->createService();
        $this->applyInput($service, $input);

        $this->entityManager->persist($service);
        $this->entityManager->flush();

        $this->auditCreated($service);

        return $service;
    }

    public function update(ServiceInterface $service, ServiceInputInterface $input): void
    {
        $this->applyInput($service, $input);
        $this->entityManager->flush();

        $this->auditUpdated($service);
    }

    public function delete(ServiceInterface $service): void
    {
        $this->auditDeleted($service);

        $this->entityManager->remove($service);
        $this->entityManager->flush();
    }

    protected function createService(): ServiceInterface
    {
        return new Service();
    }

    protected function applyInput(ServiceInterface $service, ServiceInputInterface $input): void
    {
        $service->setName($input->getName());
    }

    protected function auditCreated(ServiceInterface $service): void
    {
        $this->auditLogger->log('core', 'service.created', 'Service', $service->getId(), $this->auditPayload($service));
    }

    protected function auditUpdated(ServiceInterface $service): void
    {
        $this->auditLogger->log('core', 'service.updated', 'Service', $service->getId(), $this->auditPayload($service));
    }

    protected function auditDeleted(ServiceInterface $service): void
    {
        $this->auditLogger->log('core', 'service.deleted', 'Service', $service->getId(), $this->auditPayload($service));
    }

    protected function auditPayload(ServiceInterface $service): array
    {
        return ['name' => $service->getName()];
    }
}
