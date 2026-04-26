<?php

declare(strict_types=1);

namespace App\Module\Crm\Deal\Manager;

use App\Core\Audit\Service\AuditLogger;
use App\Module\Crm\Company\Repository\CompanyRepository;
use App\Module\Crm\Contact\Repository\ContactRepository;
use App\Module\Crm\Deal\Contract\DealManagerInterface;
use App\Module\Crm\Deal\DTO\DealInput;
use App\Module\Crm\Deal\Entity\Deal;
use App\Module\Crm\Deal\Enum\DealStageEnum;
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
    ) {}

    public function create(DealInput $input): Deal
    {
        $deal = new Deal();
        $this->applyInput($deal, $input);
        $this->entityManager->persist($deal);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'deal.created', 'Deal', $deal->getId(), ['name' => $deal->getName()]);

        return $deal;
    }

    public function update(Deal $deal, DealInput $input): void
    {
        $this->applyInput($deal, $input);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'deal.updated', 'Deal', $deal->getId(), ['name' => $deal->getName()]);
    }

    public function changeStage(Deal $deal, DealStageEnum $stage): void
    {
        $oldStage = $deal->getStage()->value;
        $deal->setStage($stage);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'deal.stage_changed', 'Deal', $deal->getId(), [
            'name' => $deal->getName(),
            'from' => $oldStage,
            'to' => $stage->value,
        ]);
    }

    public function delete(Deal $deal): void
    {
        $name = $deal->getName();
        $id = $deal->getId();

        $this->entityManager->remove($deal);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'deal.deleted', 'Deal', $id, ['name' => $name]);
    }

    private function applyInput(Deal $deal, DealInput $input): void
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
