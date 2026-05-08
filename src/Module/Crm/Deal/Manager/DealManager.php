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
use Aurora\Module\Crm\Deal\Contract\DealManagerInterface;
use Aurora\Module\Crm\Deal\DTO\DealInput;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Entity\DealInterface;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Aurora\Module\Crm\Service\CrmNotificationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DealManagerInterface::class)]
final readonly class DealManager implements DealManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContactRepository $contactRepository,
        private CompanyRepository $companyRepository,
        private AuditLogger $auditLogger,
        private CrmNotificationService $notificationService,
        private SequenceGenerator $sequenceGenerator,
        private SettingRepository $settingRepository,
    ) {}

    public function create(DealInput $input): DealInterface
    {
        $deal = new Deal();
        $this->applyInput($deal, $input);
        $prefix = $this->settingRepository->get(ApplicationParameterEnum::CrmDealPrefix->value, SequencePrefixEnum::Deal->value) ?? SequencePrefixEnum::Deal->value;
        $deal->setReference($this->sequenceGenerator->next($prefix));
        $this->entityManager->persist($deal);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'deal.created', 'Deal', $deal->getId(), ['name' => $deal->getName(), 'reference' => $deal->getReference()]);

        return $deal;
    }

    public function update(DealInterface $deal, DealInput $input): void
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

    private function applyInput(DealInterface $deal, DealInput $input): void
    {
        $deal->setName($input->name);
        $deal->setStage($input->stage);
        $deal->setValue($input->value);
        $deal->setNotes($input->notes);
        $deal->setContact($input->contactId ? $this->contactRepository->find($input->contactId) : null);
        $deal->setCompany($input->companyId ? $this->companyRepository->find($input->companyId) : null);
        $deal->setClosingDate($input->closingDate ? new DateTimeImmutable($input->closingDate) : null);
    }
}
