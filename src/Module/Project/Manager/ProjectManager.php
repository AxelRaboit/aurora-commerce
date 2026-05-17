<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Platform\User\Repository\UserRepository;
use Aurora\Module\Crm\Company\Repository\CompanyRepository;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Crm\Deal\Repository\DealRepository;
use Aurora\Module\Project\Dto\ProjectInputInterface;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectManagerInterface::class)]
class ProjectManager implements ProjectManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly UserRepository $userRepository,
        protected readonly ContactRepository $contactRepository,
        protected readonly CompanyRepository $companyRepository,
        protected readonly DealRepository $dealRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly ProjectColumnManagerInterface $columnManager,
    ) {}

    public function create(ProjectInputInterface $input): ProjectInterface
    {
        $project = $this->createProject();
        $this->applyInput($project, $input);
        $project->setReference($this->sequenceGenerator->next(SequencePrefixEnum::Project->value));
        $this->entityManager->persist($project);
        $this->columnManager->seedDefaults($project);
        $this->entityManager->flush();

        $this->auditCreated($project);

        return $project;
    }

    public function update(ProjectInterface $project, ProjectInputInterface $input): void
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
            ...$this->auditPayload($project),
            'changes' => $changes,
        ]);
    }

    public function delete(ProjectInterface $project): void
    {
        $this->auditDeleted($project);

        $this->entityManager->remove($project);
        $this->entityManager->flush();
    }

    protected function createProject(): ProjectInterface
    {
        return new Project();
    }

    protected function applyInput(ProjectInterface $project, ProjectInputInterface $input): void
    {
        $project->setTitle($input->getTitle());
        $project->setDescription($input->getDescription());
        $project->setStatus($input->getStatusEnum());
        $project->setStartDate($input->getStartDate() ? new DateTimeImmutable($input->getStartDate()) : null);
        $project->setEndDate($input->getEndDate() ? new DateTimeImmutable($input->getEndDate()) : null);
        $project->setResponsibleUser(null !== $input->getResponsibleUserId() ? $this->userRepository->find($input->getResponsibleUserId()) : null);
        $project->setCrmCompany(null !== $input->getCrmCompanyId() ? $this->companyRepository->find($input->getCrmCompanyId()) : null);
        $project->setCrmDeal(null !== $input->getCrmDealId() ? $this->dealRepository->find($input->getCrmDealId()) : null);

        // Sync crm contacts (many-to-many) in a single batch query.
        $desiredContacts = [];
        if ([] !== $input->getCrmContactIds()) {
            foreach ($this->contactRepository->findBy(['id' => $input->getCrmContactIds()]) as $contact) {
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

    protected function auditCreated(ProjectInterface $project): void
    {
        $this->auditLogger->log('project', 'project.created', 'Project', $project->getId(), $this->auditPayload($project));
    }

    protected function auditDeleted(ProjectInterface $project): void
    {
        $this->auditLogger->log('project', 'project.deleted', 'Project', $project->getId(), $this->auditPayload($project));
    }

    protected function auditPayload(ProjectInterface $project): array
    {
        return ['title' => $project->getTitle(), 'reference' => $project->getReference()];
    }

    /** @return array<string, mixed> */
    private function snapshot(ProjectInterface $project): array
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
}
