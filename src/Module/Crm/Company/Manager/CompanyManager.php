<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Crm\Company\Contract\CompanyManagerInterface;
use Aurora\Module\Crm\Company\DTO\CompanyInput;
use Aurora\Module\Crm\Company\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CompanyManagerInterface::class)]
final readonly class CompanyManager implements CompanyManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
        private SequenceGenerator $sequenceGenerator,
        private SettingRepository $settingRepository,
    ) {}

    public function create(CompanyInput $input): Company
    {
        $company = new Company();
        $this->applyInput($company, $input);
        $prefix = $this->settingRepository->get(ApplicationParameterEnum::CrmCompanyPrefix->value, SequencePrefixEnum::Company->value) ?? SequencePrefixEnum::Company->value;
        $company->setReference($this->sequenceGenerator->next($prefix));
        $this->entityManager->persist($company);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'company.created', 'Company', $company->getId(), ['name' => $company->getName(), 'reference' => $company->getReference()]);

        return $company;
    }

    public function update(Company $company, CompanyInput $input): void
    {
        $this->applyInput($company, $input);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'company.updated', 'Company', $company->getId(), ['name' => $company->getName()]);
    }

    public function delete(Company $company): void
    {
        $name = $company->getName();
        $id = $company->getId();

        $this->entityManager->remove($company);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'company.deleted', 'Company', $id, ['name' => $name]);
    }

    private function applyInput(Company $company, CompanyInput $input): void
    {
        $company->setName($input->name);
        $company->setIndustry($input->industry);
        $company->setWebsite($input->website);
        $company->setPhone($input->phone);
        $company->setAddress($input->address);
        $company->setNotes($input->notes);
    }
}
