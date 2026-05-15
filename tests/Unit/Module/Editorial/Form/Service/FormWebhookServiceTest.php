<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Form\Service;

use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Entity\FormSubmissionInterface;
use Aurora\Module\Editorial\Form\Service\FormWebhookService;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FormWebhookServiceTest extends TestCase
{
    public function testSendDoesNothingWhenWebhookUrlIsNull(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->expects(self::never())->method('request');

        $logger = $this->createStub(LoggerInterface::class);

        $form = $this->createStub(FormInterface::class);
        $form->method('getWebhookUrl')->willReturn(null);

        $submission = $this->createStub(FormSubmissionInterface::class);

        (new FormWebhookService($client, $logger))->send($form, $submission, 'fr');
    }

    public function testSendDoesNothingForEmptyWebhookUrl(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->expects(self::never())->method('request');

        $logger = $this->createStub(LoggerInterface::class);

        $form = $this->createStub(FormInterface::class);
        $form->method('getWebhookUrl')->willReturn('');

        $submission = $this->createStub(FormSubmissionInterface::class);

        (new FormWebhookService($client, $logger))->send($form, $submission, 'fr');
    }

    public function testSendPostsToWebhookUrl(): void
    {
        $form = $this->createStub(FormInterface::class);
        $form->method('getId')->willReturn(1);
        $form->method('getWebhookUrl')->willReturn('https://example.com/hook');
        $form->method('getTranslation')->willReturn(null);
        $form->method('getTranslations')->willReturn(new ArrayCollection());
        $form->method('getFields')->willReturn(new ArrayCollection());

        $submission = $this->createStub(FormSubmissionInterface::class);
        $submission->method('getData')->willReturn([]);
        $submission->method('getReference')->willReturn('SUB-001');
        $submission->method('getLocale')->willReturn('fr');
        $submission->method('getSubmittedAt')->willReturn(new DateTimeImmutable('2026-01-15T10:00:00+00:00'));
        $submission->method('getIp')->willReturn('127.0.0.1');

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects(self::once())
            ->method('request')
            ->with('POST', 'https://example.com/hook', self::callback(static function (array $options): bool {
                return isset($options['headers']['Content-Type'])
                    && 'application/json' === $options['headers']['Content-Type']
                    && isset($options['body'])
                    && 5 === $options['timeout'];
            }));

        $logger = $this->createStub(LoggerInterface::class);

        (new FormWebhookService($client, $logger))->send($form, $submission, 'fr');
    }

    public function testSendLogsWarningOnFailure(): void
    {
        $form = $this->createStub(FormInterface::class);
        $form->method('getId')->willReturn(1);
        $form->method('getWebhookUrl')->willReturn('https://example.com/hook');
        $form->method('getTranslation')->willReturn(null);
        $form->method('getTranslations')->willReturn(new ArrayCollection());
        $form->method('getFields')->willReturn(new ArrayCollection());

        $submission = $this->createStub(FormSubmissionInterface::class);
        $submission->method('getData')->willReturn([]);
        $submission->method('getReference')->willReturn('SUB-001');
        $submission->method('getLocale')->willReturn('fr');
        $submission->method('getSubmittedAt')->willReturn(new DateTimeImmutable());
        $submission->method('getIp')->willReturn(null);

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willThrowException(new RuntimeException('connection failed'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with('Form webhook delivery failed.', self::anything());

        (new FormWebhookService($client, $logger))->send($form, $submission, 'fr');
    }
}
