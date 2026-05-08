<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Service;

use Aurora\Module\Billing\Ocr\Contract\OllamaVisionClientInterface;
use Aurora\Module\Billing\Ocr\Dto\InvoiceDraft;
use Aurora\Module\Billing\Ocr\Service\InvoiceExtractor;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class InvoiceExtractorTest extends TestCase
{
    private OllamaVisionClientInterface&Stub $ollama;
    private InvoiceExtractor $extractor;

    protected function setUp(): void
    {
        $this->ollama = $this->createStub(OllamaVisionClientInterface::class);
        $this->extractor = new InvoiceExtractor($this->ollama);
    }

    public function testHydratesAFullPayload(): void
    {
        $this->ollama->method('generateStructured')->willReturn([
            'response' => [
                'supplier_name' => '  Acme Corp  ',
                'supplier_vat_number' => 'FR12345678901',
                'supplier_registration_number' => '12345678900012',
                'supplier_iban' => 'FR7630006000011234567890189',
                'supplier_bic' => 'BNPAFRPP',
                'supplier_email' => 'billing@acme.example',
                'supplier_phone' => '+33 1 23 45 67 89',
                'supplier_address' => "1 rue Demo\n75001 Paris",
                'supplier_country_code' => 'fr',
                'invoice_number' => 'INV-001',
                'purchase_order_ref' => 'PO-42',
                'issued_at' => '2025-03-14',
                'due_at' => '2025-04-14',
                'payment_terms' => '30 jours',
                'payment_method' => 'virement',
                'currency' => 'EUR',
                'total_net_cents' => 10000,
                'total_vat_cents' => 2000,
                'total_gross_cents' => 12000,
                'lines' => [
                    [
                        'label' => 'Service A',
                        'product_code' => 'PROD-001',
                        'unit' => 'h',
                        'quantity' => '2',
                        'unit_price_cents' => 5000,
                        'vat_rate_bp' => 2000,
                        'total_net_cents' => 10000,
                        'total_gross_cents' => 12000,
                    ],
                ],
                'confidence' => 0.92,
            ],
            'raw' => [],
        ]);

        $draft = $this->extractor->extract('/tmp/dummy.png', 'raw OCR text');

        self::assertInstanceOf(InvoiceDraft::class, $draft);
        self::assertSame('Acme Corp', $draft->supplierName);
        self::assertSame('FR', $draft->supplierCountryCode);
        self::assertEquals(new DateTimeImmutable('2025-03-14'), $draft->issuedAt);
        self::assertSame(12000, $draft->totalGrossCents);
        self::assertSame(0.92, $draft->confidence);
        self::assertCount(1, $draft->lines);
        self::assertSame('Service A', $draft->lines[0]->label);
        self::assertSame(2000, $draft->lines[0]->vatRateBp);
    }

    public function testCountryCodeDropsInvalidShape(): void
    {
        $this->ollama->method('generateStructured')->willReturn([
            'response' => [
                'lines' => [],
                'confidence' => 0.5,
                'supplier_country_code' => 'France', // not 2 letters
            ],
            'raw' => [],
        ]);

        $draft = $this->extractor->extract('/tmp/dummy.png', '');

        self::assertNull($draft->supplierCountryCode);
    }

    public function testGarbageDateBecomesNull(): void
    {
        $this->ollama->method('generateStructured')->willReturn([
            'response' => [
                'lines' => [],
                'confidence' => 0.5,
                'issued_at' => 'NOT A DATE',
                'due_at' => '',
            ],
            'raw' => [],
        ]);

        $draft = $this->extractor->extract('/tmp/dummy.png', '');

        self::assertNull($draft->issuedAt);
        self::assertNull($draft->dueAt);
    }

    public function testNonNumericMoneyBecomesNull(): void
    {
        $this->ollama->method('generateStructured')->willReturn([
            'response' => [
                'lines' => [],
                'confidence' => 0.5,
                'total_net_cents' => 'about 100',
            ],
            'raw' => [],
        ]);

        $draft = $this->extractor->extract('/tmp/dummy.png', '');

        self::assertNull($draft->totalNetCents);
    }

    public function testLineWithoutLabelIsSkipped(): void
    {
        $this->ollama->method('generateStructured')->willReturn([
            'response' => [
                'lines' => [
                    ['label' => 'Valid'],
                    ['unit_price_cents' => 100], // no label -> skipped
                    'not even an array',         // -> skipped
                ],
                'confidence' => 0.5,
            ],
            'raw' => [],
        ]);

        $draft = $this->extractor->extract('/tmp/dummy.png', '');

        self::assertCount(1, $draft->lines);
        self::assertSame('Valid', $draft->lines[0]->label);
    }

    public function testConfidenceDefaultsToZero(): void
    {
        $this->ollama->method('generateStructured')->willReturn([
            'response' => ['lines' => []],
            'raw' => [],
        ]);

        $draft = $this->extractor->extract('/tmp/dummy.png', '');

        self::assertSame(0.0, $draft->confidence);
    }
}
