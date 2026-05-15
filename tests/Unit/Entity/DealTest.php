<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DealTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Deal())->getId());
    }

    public function testDefaultValues(): void
    {
        $deal = new Deal();

        self::assertSame(DealStageEnum::Lead, $deal->getStage());
        self::assertNull($deal->getReference());
        self::assertNull($deal->getValue());
        self::assertNull($deal->getContact());
        self::assertNull($deal->getCompany());
        self::assertNull($deal->getClosingDate());
        self::assertNull($deal->getNotes());
    }

    public function testNameGetterAndSetter(): void
    {
        $deal = (new Deal())->setName('Big Contract');

        self::assertSame('Big Contract', $deal->getName());
    }

    public function testStageGetterAndSetter(): void
    {
        $deal = (new Deal())->setStage(DealStageEnum::Won);

        self::assertSame(DealStageEnum::Won, $deal->getStage());
    }

    public function testValueGetterAndSetter(): void
    {
        $deal = (new Deal())->setValue('50000.00');

        self::assertSame('50000.00', $deal->getValue());
    }

    public function testContactGetterAndSetter(): void
    {
        $contact = $this->createStub(ContactInterface::class);
        $deal = (new Deal())->setContact($contact);

        self::assertSame($contact, $deal->getContact());
    }

    public function testCompanyGetterAndSetter(): void
    {
        $company = new Company();
        $deal = (new Deal())->setCompany($company);

        self::assertSame($company, $deal->getCompany());
    }

    public function testClosingDateGetterAndSetter(): void
    {
        $date = new DateTimeImmutable('2026-12-31');
        $deal = (new Deal())->setClosingDate($date);

        self::assertSame($date, $deal->getClosingDate());

        $deal->setClosingDate(null);
        self::assertNull($deal->getClosingDate());
    }

    public function testNotesGetterAndSetter(): void
    {
        $deal = (new Deal())->setNotes('Important opportunity');

        self::assertSame('Important opportunity', $deal->getNotes());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $deal = (new Deal())->setReference('DEAL-001');

        self::assertSame('DEAL-001', $deal->getReference());
    }
}
