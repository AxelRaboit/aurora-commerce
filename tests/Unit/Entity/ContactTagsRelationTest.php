<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\ContactTag\Entity\ContactTag;
use PHPUnit\Framework\TestCase;

final class ContactTagsRelationTest extends TestCase
{
    public function testContactStartsWithoutContactTags(): void
    {
        $contact = new Contact();

        self::assertCount(0, $contact->getContactTags());
    }

    public function testAddContactTagsAttachesThem(): void
    {
        $contact = new Contact();
        $client = new ContactTag();
        $vip = new ContactTag();

        $contact->addContactTag($client);
        $contact->addContactTag($vip);

        self::assertCount(2, $contact->getContactTags());
        self::assertTrue($contact->getContactTags()->contains($client));
        self::assertTrue($contact->getContactTags()->contains($vip));
    }

    public function testAddContactTagIsIdempotent(): void
    {
        $contact = new Contact();
        $contactTag = new ContactTag();

        $contact->addContactTag($contactTag);
        $contact->addContactTag($contactTag);

        self::assertCount(1, $contact->getContactTags());
    }

    public function testRemoveContactTagDetachesIt(): void
    {
        $contact = new Contact();
        $contactTag = new ContactTag();

        $contact->addContactTag($contactTag);
        $contact->removeContactTag($contactTag);

        self::assertCount(0, $contact->getContactTags());
    }

    public function testClearContactTagsRemovesAll(): void
    {
        $contact = new Contact();
        $contact->addContactTag(new ContactTag());
        $contact->addContactTag(new ContactTag());

        $contact->clearContactTags();

        self::assertCount(0, $contact->getContactTags());
    }

    public function testInverseSideStartsEmpty(): void
    {
        $contactTag = new ContactTag();

        self::assertCount(0, $contactTag->getContacts());
    }
}
