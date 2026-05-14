<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\ContactTag\Dto;

use Aurora\Module\Crm\ContactTag\Dto\ContactTagInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ContactTagInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /** @return list<string> */
    private function messages(ContactTagInput $input): array
    {
        $violations = $this->validator->validate($input);
        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = (string) $violation->getMessage();
        }

        return $messages;
    }

    public function testValidInputPasses(): void
    {
        $input = new ContactTagInput(label: 'VIP', slug: 'vip', color: '#6366F1');

        self::assertSame([], $this->messages($input));
    }

    public function testEmptyLabelFails(): void
    {
        $input = new ContactTagInput(label: '', slug: null, color: '#6366F1');

        self::assertContains('backend.crm.contact_tags.errors.label_required', $this->messages($input));
    }

    public function testBlankLabelFails(): void
    {
        $input = new ContactTagInput(label: '   ', slug: null, color: '#6366F1');

        self::assertContains('backend.crm.contact_tags.errors.label_required', $this->messages($input));
    }

    public function testInvalidHexColorFails(): void
    {
        $input = new ContactTagInput(label: 'VIP', slug: null, color: 'not-a-hex');

        self::assertContains('backend.crm.contact_tags.errors.color_invalid', $this->messages($input));
    }

    public function testShortHexColorFails(): void
    {
        $input = new ContactTagInput(label: 'VIP', slug: null, color: '#FFF');

        self::assertContains('backend.crm.contact_tags.errors.color_invalid', $this->messages($input));
    }
}
