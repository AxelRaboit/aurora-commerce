<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Crm\Company\Dto\CompanyInputInterface;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Aurora\Module\Crm\Setting\CrmSettingEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CompanyManagerInterface::class)]
class CompanyManager implements CompanyManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
    ) {}

    public function create(CompanyInputInterface $input): CompanyInterface
    {
        $company = $this->createCompany();
        $this->applyInput($company, $input);
        $prefix = $this->settingRepository->getOrDefault(CrmSettingEnum::CompanyPrefix);
        $company->setReference($this->sequenceGenerator->next($prefix));
        $this->entityManager->persist($company);
        $this->entityManager->flush();

        $this->auditCreated($company);

        return $company;
    }

    public function update(CompanyInterface $company, CompanyInputInterface $input): void
    {
        $this->applyInput($company, $input);
        $this->entityManager->flush();

        $this->auditUpdated($company);
    }

    public function delete(CompanyInterface $company): void
    {
        $this->auditDeleted($company);

        $this->entityManager->remove($company);
        $this->entityManager->flush();
    }

    protected function createCompany(): CompanyInterface
    {
        return new Company();
    }

    protected function applyInput(CompanyInterface $company, CompanyInputInterface $input): void
    {
        $company->setName($input->getName());
        $company->setIndustry($input->getIndustry());
        $company->setWebsite($input->getWebsite());
        $company->setPhone($input->getPhone());
        $company->setAddress($input->getAddress());
        $company->setNotes($input->getNotes());
    }

    protected function auditCreated(CompanyInterface $company): void
    {
        $this->auditLogger->log('crm', 'company.created', 'Company', $company->getId(), $this->auditPayload($company));
    }

    protected function auditUpdated(CompanyInterface $company): void
    {
        $this->auditLogger->log('crm', 'company.updated', 'Company', $company->getId(), $this->auditPayload($company));
    }

    protected function auditDeleted(CompanyInterface $company): void
    {
        $this->auditLogger->log('crm', 'company.deleted', 'Company', $company->getId(), $this->auditPayload($company));
    }

    protected function auditPayload(CompanyInterface $company): array
    {
        return ['name' => $company->getName(), 'reference' => $company->getReference()];
    }
}
