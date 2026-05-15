<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormSubmission;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FormSubmissionTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new FormSubmission())->getId());
    }

    public function testDefaultValues(): void
    {
        $submission = new FormSubmission();

        self::assertNull($submission->getReference());
        self::assertSame([], $submission->getData());
        self::assertNull($submission->getIp());
    }

    public function testSubmittedAtInitialized(): void
    {
        self::assertInstanceOf(DateTimeImmutable::class, (new FormSubmission())->getSubmittedAt());
    }

    public function testFormGetterAndSetter(): void
    {
        $form = new Form();
        $submission = (new FormSubmission())->setForm($form);

        self::assertSame($form, $submission->getForm());
    }

    public function testDataGetterAndSetter(): void
    {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $submission = (new FormSubmission())->setData($data);

        self::assertSame($data, $submission->getData());
    }

    public function testLocaleGetterAndSetter(): void
    {
        $submission = (new FormSubmission())->setLocale('fr');

        self::assertSame('fr', $submission->getLocale());
    }

    public function testIpGetterAndSetter(): void
    {
        $submission = (new FormSubmission())->setIp('192.168.1.1');

        self::assertSame('192.168.1.1', $submission->getIp());

        $submission->setIp(null);
        self::assertNull($submission->getIp());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $submission = (new FormSubmission())->setReference('SUB-001');

        self::assertSame('SUB-001', $submission->getReference());
    }
}
