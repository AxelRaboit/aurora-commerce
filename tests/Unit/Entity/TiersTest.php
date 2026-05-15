<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use PHPUnit\Framework\TestCase;

final class TiersTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Tiers())->getId());
    }

    public function testDefaultValuesAreNull(): void
    {
        $tiers = new Tiers();

        self::assertNull($tiers->getReference());
        self::assertNull($tiers->getVatNumber());
        self::assertNull($tiers->getRegistrationNumber());
        self::assertNull($tiers->getIban());
        self::assertNull($tiers->getBic());
        self::assertNull($tiers->getEmail());
        self::assertNull($tiers->getPhone());
        self::assertNull($tiers->getAddress());
        self::assertNull($tiers->getCountryCode());
        self::assertNull($tiers->getWebsite());
        self::assertNull($tiers->getLegalForm());
        self::assertNull($tiers->getBankName());
        self::assertNull($tiers->getNotes());
        self::assertNull($tiers->getCompany());
    }

    public function testTypeAndNameGettersAndSetters(): void
    {
        $tiers = (new Tiers())->setType(TiersTypeEnum::Client)->setName('Acme Corp');

        self::assertSame(TiersTypeEnum::Client, $tiers->getType());
        self::assertSame('Acme Corp', $tiers->getName());
    }

    public function testIdentificationFields(): void
    {
        $tiers = (new Tiers())
            ->setVatNumber('FR12345678901')
            ->setRegistrationNumber('123456789')
            ->setReference('CLI-001');

        self::assertSame('FR12345678901', $tiers->getVatNumber());
        self::assertSame('123456789', $tiers->getRegistrationNumber());
        self::assertSame('CLI-001', $tiers->getReference());
    }

    public function testBankingFields(): void
    {
        $tiers = (new Tiers())
            ->setIban('FR7612345678901234567890')
            ->setBic('BNPAFRPPXXX')
            ->setBankName('BNP Paribas');

        self::assertSame('FR7612345678901234567890', $tiers->getIban());
        self::assertSame('BNPAFRPPXXX', $tiers->getBic());
        self::assertSame('BNP Paribas', $tiers->getBankName());
    }

    public function testContactFields(): void
    {
        $tiers = (new Tiers())
            ->setEmail('contact@acme.com')
            ->setPhone('+33 1 23 45 67 89')
            ->setAddress('1 rue de la Paix')
            ->setCountryCode('FR')
            ->setWebsite('https://acme.com');

        self::assertSame('contact@acme.com', $tiers->getEmail());
        self::assertSame('+33 1 23 45 67 89', $tiers->getPhone());
        self::assertSame('1 rue de la Paix', $tiers->getAddress());
        self::assertSame('FR', $tiers->getCountryCode());
        self::assertSame('https://acme.com', $tiers->getWebsite());
    }

    public function testLegalFormAndNotes(): void
    {
        $tiers = (new Tiers())->setLegalForm('SAS')->setNotes('Important client');

        self::assertSame('SAS', $tiers->getLegalForm());
        self::assertSame('Important client', $tiers->getNotes());
    }

    public function testCompanyGetterAndSetter(): void
    {
        $company = $this->createStub(CompanyInterface::class);
        $tiers = (new Tiers())->setCompany($company);

        self::assertSame($company, $tiers->getCompany());
    }
}
