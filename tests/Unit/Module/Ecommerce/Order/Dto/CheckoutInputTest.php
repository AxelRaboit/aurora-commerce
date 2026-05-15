<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\Order\Dto;

use Aurora\Module\Ecommerce\Order\Dto\CheckoutInput;
use PHPUnit\Framework\TestCase;

final class CheckoutInputTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $input = new CheckoutInput();

        self::assertSame('', $input->getEmail());
        self::assertSame('', $input->getName());
        self::assertNull($input->getAddressLine1());
        self::assertNull($input->getAddressLine2());
        self::assertNull($input->getCity());
        self::assertNull($input->getPostalCode());
        self::assertNull($input->getCountry());
        self::assertNull($input->getNotes());
    }

    public function testConstructorValues(): void
    {
        $input = new CheckoutInput(
            email: 'jane@example.com',
            name: 'Jane Doe',
            addressLine1: '1 rue de la Paix',
            addressLine2: 'Apt 5',
            city: 'Paris',
            postalCode: '75001',
            country: 'FR',
            notes: 'Leave at door',
        );

        self::assertSame('jane@example.com', $input->getEmail());
        self::assertSame('Jane Doe', $input->getName());
        self::assertSame('1 rue de la Paix', $input->getAddressLine1());
        self::assertSame('Apt 5', $input->getAddressLine2());
        self::assertSame('Paris', $input->getCity());
        self::assertSame('75001', $input->getPostalCode());
        self::assertSame('FR', $input->getCountry());
        self::assertSame('Leave at door', $input->getNotes());
    }

    public function testShippingErrorsForEmptyInput(): void
    {
        $errors = (new CheckoutInput())->shippingErrors();

        self::assertArrayHasKey('addressLine1', $errors);
        self::assertArrayHasKey('city', $errors);
        self::assertArrayHasKey('postalCode', $errors);
        self::assertArrayHasKey('country', $errors);
    }

    public function testShippingErrorsForValidShipping(): void
    {
        $input = new CheckoutInput(
            addressLine1: '1 rue de la Paix',
            city: 'Paris',
            postalCode: '75001',
            country: 'FR',
        );

        self::assertSame([], $input->shippingErrors());
    }

    public function testShippingErrorsForInvalidCountry(): void
    {
        $input = new CheckoutInput(
            addressLine1: '1 rue',
            city: 'Paris',
            postalCode: '75001',
            country: 'XX',
        );

        $errors = $input->shippingErrors();
        self::assertArrayHasKey('country', $errors);
        self::assertStringContainsString('invalid', $errors['country']);
    }
}
