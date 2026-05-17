<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Project())->getId());
    }

    public function testCollectionsInitialized(): void
    {
        $project = new Project();

        self::assertCount(0, $project->getCrmContacts());
        self::assertCount(0, $project->getTasks());
        self::assertCount(0, $project->getColumns());
    }

    public function testDefaultValues(): void
    {
        $project = new Project();

        self::assertNull($project->getReference());
        self::assertNull($project->getDescription());
        self::assertSame(ProjectStatusEnum::Draft, $project->getStatus());
        self::assertNull($project->getStartDate());
        self::assertNull($project->getEndDate());
        self::assertNull($project->getResponsibleUser());
        self::assertNull($project->getCrmCompany());
        self::assertNull($project->getCrmDeal());
    }

    public function testTitleGetterAndSetter(): void
    {
        $project = (new Project())->setTitle('Aurora Project');

        self::assertSame('Aurora Project', $project->getTitle());
    }

    public function testStatusGetterAndSetter(): void
    {
        $project = (new Project())->setStatus(ProjectStatusEnum::Active);

        self::assertSame(ProjectStatusEnum::Active, $project->getStatus());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $project = (new Project())->setDescription('Project description');

        self::assertSame('Project description', $project->getDescription());

        $project->setDescription(null);
        self::assertNull($project->getDescription());
    }

    public function testStartAndEndDate(): void
    {
        $start = new DateTimeImmutable('2026-01-01');
        $end = new DateTimeImmutable('2026-12-31');

        $project = (new Project())->setStartDate($start)->setEndDate($end);

        self::assertSame($start, $project->getStartDate());
        self::assertSame($end, $project->getEndDate());
    }

    public function testResponsibleUserGetterAndSetter(): void
    {
        $user = new User();
        $project = (new Project())->setResponsibleUser($user);

        self::assertSame($user, $project->getResponsibleUser());
    }

    public function testCrmCompanyAndDealGettersAndSetters(): void
    {
        $company = new Company();
        $deal = new Deal();
        $project = (new Project())->setCrmCompany($company)->setCrmDeal($deal);

        self::assertSame($company, $project->getCrmCompany());
        self::assertSame($deal, $project->getCrmDeal());
    }

    public function testAddAndRemoveCrmContact(): void
    {
        $project = new Project();
        $contact = new Contact();

        $project->addCrmContact($contact);
        self::assertCount(1, $project->getCrmContacts());

        $project->addCrmContact($contact);
        self::assertCount(1, $project->getCrmContacts(), 'duplicate ignored');

        $project->removeCrmContact($contact);
        self::assertCount(0, $project->getCrmContacts());
    }

    public function testAddColumnIgnoresDuplicate(): void
    {
        $project = new Project();
        $column = new ProjectColumn();

        $project->addColumn($column);
        $project->addColumn($column);

        self::assertCount(1, $project->getColumns());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $project = (new Project())->setReference('PROJ-001');

        self::assertSame('PROJ-001', $project->getReference());
    }
}
