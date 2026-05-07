<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Crm\Company\Repository\CompanyRepository;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Crm\Deal\Repository\DealRepository;
use Aurora\Module\Project\DTO\ProjectInput;
use Aurora\Module\Project\Entity\Project;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ContactRepository $contactRepository,
        private CompanyRepository $companyRepository,
        private DealRepository $dealRepository,
        private AuditLogger $auditLogger,
        private SequenceGenerator $sequenceGenerator,
        private ProjectColumnManager $columnManager,
    ) {}

    public function create(ProjectInput $input): Project
    {
        $project = new Project();
        $this->applyInput($project, $input);
        $project->setReference($this->sequenceGenerator->next(SequencePrefixEnum::Project->value));
        $this->entityManager->persist($project);
        $this->columnManager->seedDefaults($project);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'project.created', 'Project', $project->getId(), ['title' => $project->getTitle(), 'reference' => $project->getReference()]);

        return $project;
    }

    public function update(Project $project, ProjectInput $input): void
    {
        $before = $this->snapshot($project);
        $this->applyInput($project, $input);
        $this->entityManager->flush();
        $after = $this->snapshot($project);

        $changes = [];
        foreach ($after as $field => $value) {
            if (($before[$field] ?? null) !== $value) {
                $changes[$field] = ['from' => $before[$field] ?? null, 'to' => $value];
            }
        }

        $this->auditLogger->log('project', 'project.updated', 'Project', $project->getId(), [
            'title' => $project->getTitle(),
            'changes' => $changes,
        ]);
    }

    public function delete(Project $project): void
    {
        $title = $project->getTitle();
        $id = $project->getId();

        $this->entityManager->remove($project);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'project.deleted', 'Project', $id, ['title' => $title]);
    }

    /** @return array<string, mixed> */
    private function snapshot(Project $project): array
    {
        $contactIds = array_map(static fn ($c): int => (int) $c->getId(), $project->getCrmContacts()->toArray());
        sort($contactIds);

        return [
            'title' => $project->getTitle(),
            'description' => $project->getDescription(),
            'status' => $project->getStatus()->value,
            'startDate' => $project->getStartDate()?->format('Y-m-d'),
            'endDate' => $project->getEndDate()?->format('Y-m-d'),
            'responsibleUserId' => $project->getResponsibleUser()?->getId(),
            'crmCompanyId' => $project->getCrmCompany()?->getId(),
            'crmDealId' => $project->getCrmDeal()?->getId(),
            'crmContactIds' => $contactIds,
        ];
    }

    private function applyInput(Project $project, ProjectInput $input): void
    {
        $project->setTitle($input->title);
        $project->setDescription($input->description);
        $project->setStatus($input->statusEnum());
        $project->setStartDate($input->startDate ? new DateTimeImmutable($input->startDate) : null);
        $project->setEndDate($input->endDate ? new DateTimeImmutable($input->endDate) : null);
        $project->setResponsibleUser($input->responsibleUserId ? $this->userRepository->find($input->responsibleUserId) : null);
        $project->setCrmCompany($input->crmCompanyId ? $this->companyRepository->find($input->crmCompanyId) : null);
        $project->setCrmDeal($input->crmDealId ? $this->dealRepository->find($input->crmDealId) : null);

        // Sync crm contacts (many-to-many) in a single batch query.
        $desiredContacts = [];
        if ([] !== $input->crmContactIds) {
            foreach ($this->contactRepository->findBy(['id' => $input->crmContactIds]) as $contact) {
                $desiredContacts[(int) $contact->getId()] = $contact;
            }
        }

        foreach ($project->getCrmContacts()->toArray() as $existing) {
            if (!isset($desiredContacts[(int) $existing->getId()])) {
                $project->removeCrmContact($existing);
            }
        }

        foreach ($desiredContacts as $contact) {
            $project->addCrmContact($contact);
        }
    }
}
