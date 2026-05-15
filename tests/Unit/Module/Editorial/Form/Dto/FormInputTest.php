<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Form\Dto;

use Aurora\Module\Editorial\Form\Dto\FormInput;
use PHPUnit\Framework\TestCase;

final class FormInputTest extends TestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $translations = ['fr' => ['title' => 'Contact', 'slug' => 'contact', 'description' => null]];
        $steps = [['title' => 'Step 1']];

        $input = new FormInput(
            notifyEmail: 'admin@example.com',
            webhookUrl: 'https://example.com/hook',
            crmSync: true,
            steps: $steps,
            active: true,
            translations: $translations,
        );

        self::assertSame('admin@example.com', $input->getNotifyEmail());
        self::assertSame('https://example.com/hook', $input->getWebhookUrl());
        self::assertTrue($input->isCrmSync());
        self::assertSame($steps, $input->getSteps());
        self::assertTrue($input->isActive());
        self::assertSame($translations, $input->getTranslations());
    }

    public function testNullableAndFalseValues(): void
    {
        $input = new FormInput(
            notifyEmail: null,
            webhookUrl: null,
            crmSync: false,
            steps: null,
            active: false,
            translations: [],
        );

        self::assertNull($input->getNotifyEmail());
        self::assertNull($input->getWebhookUrl());
        self::assertFalse($input->isCrmSync());
        self::assertNull($input->getSteps());
        self::assertFalse($input->isActive());
        self::assertSame([], $input->getTranslations());
    }
}
