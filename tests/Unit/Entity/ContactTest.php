<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Enum\ContactSourceEnum;
use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use PHPUnit\Framework\TestCase;

final class ContactTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Contact())->getId());
    }

    public function testContactTagsCollectionInitialized(): void
    {
        self::assertCount(0, (new Contact())->getContactTags());
    }

    public function testDefaultValues(): void
    {
        $contact = new Contact();

        self::assertNull($contact->getReference());
        self::assertNull($contact->getEmail());
        self::assertNull($contact->getPhone());
        self::assertNull($contact->getCompany());
        self::assertNull($contact->getNotes());
        self::assertNull($contact->getSource());
    }

    public function testFirstAndLastNameGettersAndSetters(): void
    {
        $contact = (new Contact())->setFirstName('Jane')->setLastName('Doe');

        self::assertSame('Jane', $contact->getFirstName());
        self::assertSame('Doe', $contact->getLastName());
    }

    public function testGetFullNameCombinesNames(): void
    {
        $contact = (new Contact())->setFirstName('Jane')->setLastName('Doe');

        self::assertSame('Jane Doe', $contact->getFullName());
    }

    public function testEmailAndPhoneGettersAndSetters(): void
    {
        $contact = (new Contact())->setEmail('jane@example.com')->setPhone('+33123456789');

        self::assertSame('jane@example.com', $contact->getEmail());
        self::assertSame('+33123456789', $contact->getPhone());
    }

    public function testCompanyGetterAndSetter(): void
    {
        $company = (new Company())->setName('Acme');
        $contact = (new Contact())->setCompany($company);

        self::assertSame($company, $contact->getCompany());
    }

    public function testGetDisplayCompanyReturnsCompanyName(): void
    {
        $company = (new Company())->setName('Acme');
        $contact = (new Contact())->setCompany($company);

        self::assertSame('Acme', $contact->getDisplayCompany());
    }

    public function testGetDisplayCompanyReturnsNullWhenNoCompany(): void
    {
        self::assertNull((new Contact())->getDisplayCompany());
    }

    public function testNotesGetterAndSetter(): void
    {
        $contact = (new Contact())->setNotes('Important contact');

        self::assertSame('Important contact', $contact->getNotes());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $contact = (new Contact())->setReference('CT-001');

        self::assertSame('CT-001', $contact->getReference());
    }

    public function testSourceGetterAndSetter(): void
    {
        $contact = (new Contact())->setSource(ContactSourceEnum::Manual);

        self::assertSame(ContactSourceEnum::Manual, $contact->getSource());
    }

    public function testAddAndRemoveContactTag(): void
    {
        $contact = new Contact();
        $tag = $this->createStub(ContactTagInterface::class);

        $contact->addContactTag($tag);
        self::assertCount(1, $contact->getContactTags());

        $contact->addContactTag($tag);
        self::assertCount(1, $contact->getContactTags(), 'duplicate ignored');

        $contact->removeContactTag($tag);
        self::assertCount(0, $contact->getContactTags());
    }

    public function testClearContactTags(): void
    {
        $contact = new Contact();
        $contact->addContactTag($this->createStub(ContactTagInterface::class));
        $contact->addContactTag($this->createStub(ContactTagInterface::class));

        self::assertCount(2, $contact->getContactTags());

        $contact->clearContactTags();

        self::assertCount(0, $contact->getContactTags());
    }
}
