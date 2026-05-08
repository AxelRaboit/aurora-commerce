<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Crm\Company\Repository\CompanyRepository;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Crm\Deal\DTO\DealInputInterface;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Entity\DealInterface;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Aurora\Module\Crm\Service\CrmNotificationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DealManagerInterface::class)]
class DealManager implements DealManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ContactRepository $contactRepository,
        protected readonly CompanyRepository $companyRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly CrmNotificationService $notificationService,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
    ) {}

    public function create(DealInputInterface $input): DealInterface
    {
        $deal = $this->createDeal();
        $this->applyInput($deal, $input);
        $prefix = $this->settingRepository->get(ApplicationParameterEnum::CrmDealPrefix->value, SequencePrefixEnum::Deal->value) ?? SequencePrefixEnum::Deal->value;
        $deal->setReference($this->sequenceGenerator->next($prefix));
        $this->entityManager->persist($deal);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'deal.created', 'Deal', $deal->getId(), ['name' => $deal->getName(), 'reference' => $deal->getReference()]);

        return $deal;
    }

    public function update(DealInterface $deal, DealInputInterface $input): void
    {
        $this->applyInput($deal, $input);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'deal.updated', 'Deal', $deal->getId(), ['name' => $deal->getName()]);
    }

    public function changeStage(DealInterface $deal, DealStageEnum $stage): void
    {
        $oldStage = $deal->getStage()->value;
        $deal->setStage($stage);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'deal.stage_changed', 'Deal', $deal->getId(), [
            'name' => $deal->getName(),
            'from' => $oldStage,
            'to' => $stage->value,
        ]);

        $this->notificationService->notifyDealStageChanged($deal, $stage);
    }

    public function delete(DealInterface $deal): void
    {
        $name = $deal->getName();
        $id = $deal->getId();

        $this->entityManager->remove($deal);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'deal.deleted', 'Deal', $id, ['name' => $name]);
    }

    protected function createDeal(): DealInterface
    {
        return new Deal();
    }

    protected function applyInput(DealInterface $deal, DealInputInterface $input): void
    {
        $deal->setName($input->getName());
        $deal->setStage($input->getStage());
        $deal->setValue($input->getValue());
        $deal->setNotes($input->getNotes());
        $deal->setContact($input->getContactId() ? $this->contactRepository->find($input->getContactId()) : null);
        $deal->setCompany($input->getCompanyId() ? $this->companyRepository->find($input->getCompanyId()) : null);
        $deal->setClosingDate($input->getClosingDate() ? new DateTimeImmutable($input->getClosingDate()) : null);
    }
}
