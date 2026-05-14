<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;

interface InvoiceInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getNumber(): ?string;

    public function setNumber(?string $number): self;

    public function getSupplierNumber(): ?string;

    public function setSupplierNumber(?string $supplierNumber): self;

    public function getTiers(): ?TiersInterface;

    public function setTiers(?TiersInterface $tiers): self;

    public function getBuyerTiers(): ?TiersInterface;

    public function setBuyerTiers(?TiersInterface $buyerTiers): self;

    public function getStatus(): InvoiceStatusEnum;

    public function setStatus(InvoiceStatusEnum $status): self;

    public function getIssuedAt(): ?DateTimeImmutable;

    public function setIssuedAt(?DateTimeImmutable $issuedAt): self;

    public function getDueAt(): ?DateTimeImmutable;

    public function setDueAt(?DateTimeImmutable $dueAt): self;

    public function getPaidAt(): ?DateTimeImmutable;

    public function setPaidAt(?DateTimeImmutable $paidAt): self;

    public function getCurrency(): CurrencyEnum;

    public function setCurrency(CurrencyEnum $currency): self;

    public function getTotalNetCents(): ?int;

    public function setTotalNetCents(?int $totalNetCents): self;

    public function getTotalVatCents(): ?int;

    public function setTotalVatCents(?int $totalVatCents): self;

    public function getTotalGrossCents(): ?int;

    public function setTotalGrossCents(?int $totalGrossCents): self;

    public function getSubtotalCents(): ?int;

    public function setSubtotalCents(?int $subtotalCents): self;

    public function getDiscountCents(): ?int;

    public function setDiscountCents(?int $discountCents): self;

    public function getFreightCents(): ?int;

    public function setFreightCents(?int $freightCents): self;

    public function getInsuranceCents(): ?int;

    public function setInsuranceCents(?int $insuranceCents): self;

    public function getDiscountRateBp(): ?int;

    public function setDiscountRateBp(?int $discountRateBp): self;

    public function getReference(): ?string;

    public function setReference(?string $reference): self;

    public function getProject(): ?string;

    public function setProject(?string $project): self;

    public function getIncoterms(): ?string;

    public function setIncoterms(?string $incoterms): self;

    public function getDeliveryDate(): ?DateTimeImmutable;

    public function setDeliveryDate(?DateTimeImmutable $deliveryDate): self;

    public function getReverseCharge(): ?bool;

    public function setReverseCharge(?bool $reverseCharge): self;

    public function getBankDetails(): ?string;

    public function setBankDetails(?string $bankDetails): self;

    public function getPurchaseOrderRef(): ?string;

    public function setPurchaseOrderRef(?string $purchaseOrderRef): self;

    public function getPaymentTerms(): ?string;

    public function setPaymentTerms(?string $paymentTerms): self;

    public function getPaymentMethod(): ?string;

    public function setPaymentMethod(?string $paymentMethod): self;

    public function getNotes(): ?string;

    public function setNotes(?string $notes): self;

    public function getDocument(): ?MediaInterface;

    public function setDocument(?MediaInterface $document): self;

    public function getOcrJob(): ?OcrJobInterface;

    public function setOcrJob(?OcrJobInterface $ocrJob): self;

    /** @return Collection<int, InvoiceLineInterface> */
    public function getLines(): Collection;

    public function addLine(InvoiceLineInterface $line): self;

    public function removeLine(InvoiceLineInterface $line): self;

    public function assertEditable(): void;

    public function isCancelled(): bool;

    public function getCreditNote(): ?InvoiceInterface;

    public function setCreditNote(?InvoiceInterface $creditNote): self;

    public function getCancelledInvoice(): ?InvoiceInterface;
}
