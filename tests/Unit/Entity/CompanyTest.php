<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Crm\Company\Entity\Company;
use PHPUnit\Framework\TestCase;

final class CompanyTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Company())->getId());
    }

    public function testOptionalFieldsNullByDefault(): void
    {
        $company = new Company();

        self::assertNull($company->getReference());
        self::assertNull($company->getIndustry());
        self::assertNull($company->getWebsite());
        self::assertNull($company->getPhone());
        self::assertNull($company->getAddress());
        self::assertNull($company->getNotes());
    }

    public function testNameGetterAndSetter(): void
    {
        $company = (new Company())->setName('Acme Corp');

        self::assertSame('Acme Corp', $company->getName());
    }

    public function testIndustryGetterAndSetter(): void
    {
        $company = (new Company())->setIndustry('Technology');

        self::assertSame('Technology', $company->getIndustry());
    }

    public function testWebsiteAndPhoneGettersAndSetters(): void
    {
        $company = (new Company())->setWebsite('https://acme.com')->setPhone('+33123456789');

        self::assertSame('https://acme.com', $company->getWebsite());
        self::assertSame('+33123456789', $company->getPhone());
    }

    public function testAddressAndNotesGettersAndSetters(): void
    {
        $company = (new Company())->setAddress('1 rue de la Paix')->setNotes('Important client');

        self::assertSame('1 rue de la Paix', $company->getAddress());
        self::assertSame('Important client', $company->getNotes());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $company = (new Company())->setReference('CMP-001');

        self::assertSame('CMP-001', $company->getReference());
    }
}
