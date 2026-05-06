<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\DTO;

use Aurora\Core\Locale\Enum\CountryEnum;
use Aurora\Module\Ecommerce\Order\DTO\CheckoutInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CheckoutInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testFromArrayTrimsAndUppercasesCountry(): void
    {
        $input = CheckoutInput::fromArray([
            'email' => '  buyer@example.com  ',
            'name' => '  Alice  ',
            'addressLine1' => '  1 rue Test  ',
            'city' => '  Paris  ',
            'postalCode' => '  75001  ',
            'country' => 'fr',
            'addressLine2' => '   ',
            'notes' => '   ',
        ]);

        self::assertSame('buyer@example.com', $input->email);
        self::assertSame('Alice', $input->name);
        self::assertSame('1 rue Test', $input->addressLine1);
        self::assertSame('Paris', $input->city);
        self::assertSame('75001', $input->postalCode);
        self::assertSame('FR', $input->country);
        self::assertNull($input->addressLine2);
        self::assertNull($input->notes);
    }

    public function testValidationFailsWhenEmailMissing(): void
    {
        $input = new CheckoutInput(email: '', name: 'Alice');

        $errors = $this->validator->validate($input);
        self::assertGreaterThanOrEqual(1, $errors->count());

        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error->getMessage();
        }
        self::assertContains('frontend.checkout.errors.email_required', $messages);
    }

    public function testValidationFailsWhenEmailInvalid(): void
    {
        $input = new CheckoutInput(email: 'not-an-email', name: 'Alice');

        $errors = $this->validator->validate($input);
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error->getMessage();
        }
        self::assertContains('frontend.checkout.errors.email_invalid', $messages);
    }

    public function testValidationPassesWithMinimumValidPayload(): void
    {
        $input = new CheckoutInput(email: 'buyer@example.com', name: 'Alice');

        $errors = $this->validator->validate($input);
        self::assertCount(0, $errors);
    }

    public function testShippingErrorsReturnsFieldsForPhysicalCart(): void
    {
        $input = new CheckoutInput(email: 'a@b.c', name: 'A');

        $errors = $input->shippingErrors();

        self::assertSame('frontend.checkout.errors.address_required', $errors['addressLine1']);
        self::assertSame('frontend.checkout.errors.city_required', $errors['city']);
        self::assertSame('frontend.checkout.errors.postal_required', $errors['postalCode']);
        self::assertSame('frontend.checkout.errors.country_required', $errors['country']);
    }

    public function testShippingErrorsRejectsUnsupportedCountry(): void
    {
        $input = new CheckoutInput(
            email: 'a@b.c',
            name: 'A',
            addressLine1: '1 rue',
            city: 'Paris',
            postalCode: '75001',
            country: 'ZZ',
        );

        $errors = $input->shippingErrors();
        self::assertSame('frontend.checkout.errors.country_invalid', $errors['country']);
    }

    public function testShippingErrorsEmptyForCompletePayload(): void
    {
        $input = new CheckoutInput(
            email: 'a@b.c',
            name: 'A',
            addressLine1: '1 rue',
            city: 'Paris',
            postalCode: '75001',
            country: CountryEnum::default()->value,
        );

        self::assertSame([], $input->shippingErrors());
    }
}
