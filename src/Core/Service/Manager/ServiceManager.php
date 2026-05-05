<?php

declare(strict_types=1);

namespace Aurora\Core\Service\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Service\DTO\ServiceInput;
use Aurora\Core\Service\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ServiceManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
    ) {}

    public function create(ServiceInput $input): Service
    {
        $service = new Service()->setName($input->name);

        $this->entityManager->persist($service);
        $this->entityManager->flush();

        $this->auditLogger->log('core', 'service.created', 'Service', $service->getId(), ['name' => $service->getName()]);

        return $service;
    }

    public function update(Service $service, ServiceInput $input): void
    {
        $service->setName($input->name);
        $this->entityManager->flush();

        $this->auditLogger->log('core', 'service.updated', 'Service', $service->getId(), ['name' => $service->getName()]);
    }

    public function delete(Service $service): void
    {
        $this->auditLogger->log('core', 'service.deleted', 'Service', $service->getId(), ['name' => $service->getName()]);

        $this->entityManager->remove($service);
        $this->entityManager->flush();
    }
}
