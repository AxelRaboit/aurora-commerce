<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Serializer;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectLabel;
use Aurora\Module\Project\Entity\ProjectSprint;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use Aurora\Module\Project\Repository\ProjectLabelRepository;
use Aurora\Module\Project\Repository\ProjectSprintRepository;
use Aurora\Module\Project\Serializer\ProjectColumnSerializer;
use Aurora\Module\Project\Serializer\ProjectSerializer;
use Aurora\Module\Project\Serializer\ProjectSprintSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class ProjectSerializerTest extends TestCase
{
    private ProjectSerializer $serializer;
    private ProjectLabelRepository $labelRepository;
    private ProjectSprintRepository $sprintRepository;

    protected function setUp(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $this->labelRepository = $this->createMock(ProjectLabelRepository::class);
        $this->sprintRepository = $this->createMock(ProjectSprintRepository::class);

        $this->serializer = new ProjectSerializer(
            $translator,
            new ProjectColumnSerializer(),
            $this->labelRepository,
            $this->sprintRepository,
            new ProjectSprintSerializer(),
        );
    }

    private function makeProject(): Project
    {
        $project = new Project();
        $project->setReference('PRJ-000001')
            ->setTitle('Demo')
            ->setDescription('Description')
            ->setStatus(ProjectStatusEnum::Active)
            ->setStartDate(new DateTimeImmutable('2026-01-15'))
            ->setEndDate(new DateTimeImmutable('2026-06-30'));

        (new ReflectionProperty(Project::class, 'id'))->setValue($project, 1);
        (new ReflectionProperty(Project::class, 'createdAt'))->setValue($project, new DateTimeImmutable('2026-01-01'));
        (new ReflectionProperty(Project::class, 'updatedAt'))->setValue($project, new DateTimeImmutable('2026-01-02'));

        return $project;
    }

    public function testSerializeIncludesScalarFields(): void
    {
        $project = $this->makeProject();
        $this->labelRepository->method('findByProject')->willReturn([]);
        $this->sprintRepository->method('findByProject')->willReturn([]);

        $payload = $this->serializer->serialize($project);

        self::assertSame(1, $payload['id']);
        self::assertSame('PRJ-000001', $payload['reference']);
        self::assertSame('Demo', $payload['title']);
        self::assertSame('Description', $payload['description']);
        self::assertSame('active', $payload['status']);
        self::assertSame('2026-01-15', $payload['startDate']);
        self::assertSame('2026-06-30', $payload['endDate']);
    }

    public function testSerializeNullCoalescesAbsentRelations(): void
    {
        $project = $this->makeProject();
        $this->labelRepository->method('findByProject')->willReturn([]);
        $this->sprintRepository->method('findByProject')->willReturn([]);

        $payload = $this->serializer->serialize($project);

        self::assertNull($payload['responsibleUser']);
        self::assertNull($payload['crmCompany']);
        self::assertNull($payload['crmDeal']);
        self::assertSame([], $payload['crmContacts']);
        self::assertSame([], $payload['columns']);
        self::assertSame([], $payload['labels']);
        self::assertSame([], $payload['sprints']);
        self::assertSame(0, $payload['taskCount']);
    }

    public function testSerializeIncludesRelationSummaries(): void
    {
        $project = $this->makeProject();

        $user = new User();
        $user->setName('Alice');
        (new ReflectionProperty(User::class, 'id'))->setValue($user, 5);
        $project->setResponsibleUser($user);

        $company = new Company();
        $company->setName('Acme');
        (new ReflectionProperty(Company::class, 'id'))->setValue($company, 9);
        $project->setCrmCompany($company);

        $deal = new Deal();
        $deal->setName('Big Deal');
        (new ReflectionProperty(Deal::class, 'id'))->setValue($deal, 7);
        $project->setCrmDeal($deal);

        $contact = new Contact();
        $contact->setFirstName('Bob')->setLastName('Builder');
        (new ReflectionProperty(Contact::class, 'id'))->setValue($contact, 3);
        $project->addCrmContact($contact);

        $this->labelRepository->method('findByProject')->willReturn([]);
        $this->sprintRepository->method('findByProject')->willReturn([]);

        $payload = $this->serializer->serialize($project);

        self::assertSame(['id' => 5, 'name' => 'Alice'], $payload['responsibleUser']);
        self::assertSame(['id' => 9, 'name' => 'Acme'], $payload['crmCompany']);
        self::assertSame(['id' => 7, 'name' => 'Big Deal'], $payload['crmDeal']);
        self::assertSame([['id' => 3, 'name' => 'Bob Builder']], $payload['crmContacts']);
    }

    public function testSerializeIncludesColumnsAndLabels(): void
    {
        $project = $this->makeProject();

        $column = new ProjectColumn();
        $column->setProject($project)->setLabel('À faire')->setPosition(0);
        (new ReflectionProperty(ProjectColumn::class, 'id'))->setValue($column, 11);
        $project->addColumn($column);

        $label = new ProjectLabel();
        $label->setProject($project)->setName('Bug')->setColor('rose');
        (new ReflectionProperty(ProjectLabel::class, 'id'))->setValue($label, 22);

        $sprint = new ProjectSprint();
        $sprint->setProject($project)->setName('Sprint 1');
        (new ReflectionProperty(ProjectSprint::class, 'id'))->setValue($sprint, 33);

        $this->labelRepository->method('findByProject')->willReturn([$label]);
        $this->sprintRepository->method('findByProject')->willReturn([$sprint]);

        $payload = $this->serializer->serialize($project);

        self::assertCount(1, $payload['columns']);
        self::assertSame(11, $payload['columns'][0]['id']);
        self::assertSame('À faire', $payload['columns'][0]['label']);

        self::assertSame([['id' => 22, 'name' => 'Bug', 'color' => 'rose']], $payload['labels']);
        self::assertCount(1, $payload['sprints']);
        self::assertSame(33, $payload['sprints'][0]['id']);
    }
}
