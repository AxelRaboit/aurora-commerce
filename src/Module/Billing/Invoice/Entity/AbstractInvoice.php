<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Entity;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractInvoice implements InvoiceInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, nullable: true)]
    protected ?string $number = null;

    /** Invoice number as printed on the supplier's document (extracted by OCR). */
    #[ORM\Column(length: 64, nullable: true)]
    protected ?string $supplierNumber = null;

    #[ORM\ManyToOne(targetEntity: TiersInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?TiersInterface $tiers = null;

    #[ORM\ManyToOne(targetEntity: TiersInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?TiersInterface $buyerTiers = null;

    #[ORM\Column(length: 16, enumType: InvoiceStatusEnum::class, options: ['default' => 'draft'])]
    protected InvoiceStatusEnum $status = InvoiceStatusEnum::Draft;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $issuedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $dueAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $paidAt = null;

    #[ORM\Column(length: 3, enumType: CurrencyEnum::class, options: ['default' => 'EUR'])]
    protected CurrencyEnum $currency = CurrencyEnum::EUR;

    #[ORM\Column(nullable: true)]
    protected ?int $subtotalCents = null;

    #[ORM\Column(nullable: true)]
    protected ?int $totalNetCents = null;

    #[ORM\Column(nullable: true)]
    protected ?int $totalVatCents = null;

    #[ORM\Column(nullable: true)]
    protected ?int $totalGrossCents = null;

    #[ORM\Column(nullable: true)]
    protected ?int $discountCents = null;

    #[ORM\Column(nullable: true)]
    protected ?int $freightCents = null;

    #[ORM\Column(nullable: true)]
    protected ?int $insuranceCents = null;

    #[ORM\Column(nullable: true)]
    protected ?int $discountRateBp = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $project = null;

    #[ORM\Column(length: 50, nullable: true)]
    protected ?string $incoterms = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $deliveryDate = null;

    #[ORM\Column(nullable: true, options: ['default' => false])]
    protected ?bool $reverseCharge = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $bankDetails = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $purchaseOrderRef = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $paymentTerms = null;

    #[ORM\Column(length: 50, nullable: true)]
    protected ?string $paymentMethod = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $notes = null;

    /** The credit note (avoir) that cancels this invoice. Null if not cancelled. */
    #[ORM\OneToOne(targetEntity: InvoiceInterface::class, inversedBy: 'cancelledInvoice')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?InvoiceInterface $creditNote = null;

    /** When this invoice IS a credit note, points back to the invoice it cancels. */
    #[ORM\OneToOne(targetEntity: InvoiceInterface::class, mappedBy: 'creditNote')]
    protected ?InvoiceInterface $cancelledInvoice = null;

    /** Original document (PDF/image) — owned by Core/Media. */
    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?MediaInterface $document = null;

    /** Traceability link to the OCR job that produced this draft. */
    #[ORM\ManyToOne(targetEntity: OcrJobInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?OcrJobInterface $ocrJob = null;

    /** @var Collection<int, InvoiceLineInterface> */
    #[ORM\OneToMany(targetEntity: InvoiceLineInterface::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    public function assertEditable(): void
    {
        if (!$this->status->isEditable()) {
            throw new InvalidArgumentException('backend.billing.invoices.update.locked');
        }
    }

    public function isCancelled(): bool
    {
        return $this->creditNote instanceof InvoiceInterface;
    }

    public function getCreditNote(): ?InvoiceInterface
    {
        return $this->creditNote;
    }

    public function setCreditNote(?InvoiceInterface $creditNote): self
    {
        $this->creditNote = $creditNote;

        return $this;
    }

    public function getCancelledInvoice(): ?InvoiceInterface
    {
        return $this->cancelledInvoice;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getSupplierNumber(): ?string
    {
        return $this->supplierNumber;
    }

    public function setSupplierNumber(?string $supplierNumber): self
    {
        $this->supplierNumber = $supplierNumber;

        return $this;
    }

    public function getTiers(): ?TiersInterface
    {
        return $this->tiers;
    }

    public function setTiers(?TiersInterface $tiers): self
    {
        $this->tiers = $tiers;

        return $this;
    }

    public function getBuyerTiers(): ?TiersInterface
    {
        return $this->buyerTiers;
    }

    public function setBuyerTiers(?TiersInterface $buyerTiers): self
    {
        $this->buyerTiers = $buyerTiers;

        return $this;
    }

    public function getStatus(): InvoiceStatusEnum
    {
        return $this->status;
    }

    public function setStatus(InvoiceStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getIssuedAt(): ?DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(?DateTimeImmutable $issuedAt): self
    {
        $this->issuedAt = $issuedAt;

        return $this;
    }

    public function getDueAt(): ?DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function setDueAt(?DateTimeImmutable $dueAt): self
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?DateTimeImmutable $paidAt): self
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getCurrency(): CurrencyEnum
    {
        return $this->currency;
    }

    public function setCurrency(CurrencyEnum $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getTotalNetCents(): ?int
    {
        return $this->totalNetCents;
    }

    public function setTotalNetCents(?int $totalNetCents): self
    {
        $this->totalNetCents = $totalNetCents;

        return $this;
    }

    public function getTotalVatCents(): ?int
    {
        return $this->totalVatCents;
    }

    public function setTotalVatCents(?int $totalVatCents): self
    {
        $this->totalVatCents = $totalVatCents;

        return $this;
    }

    public function getTotalGrossCents(): ?int
    {
        return $this->totalGrossCents;
    }

    public function setTotalGrossCents(?int $totalGrossCents): self
    {
        $this->totalGrossCents = $totalGrossCents;

        return $this;
    }

    public function getSubtotalCents(): ?int
    {
        return $this->subtotalCents;
    }

    public function setSubtotalCents(?int $subtotalCents): self
    {
        $this->subtotalCents = $subtotalCents;

        return $this;
    }

    public function getDiscountCents(): ?int
    {
        return $this->discountCents;
    }

    public function setDiscountCents(?int $discountCents): self
    {
        $this->discountCents = $discountCents;

        return $this;
    }

    public function getFreightCents(): ?int
    {
        return $this->freightCents;
    }

    public function setFreightCents(?int $freightCents): self
    {
        $this->freightCents = $freightCents;

        return $this;
    }

    public function getInsuranceCents(): ?int
    {
        return $this->insuranceCents;
    }

    public function setInsuranceCents(?int $insuranceCents): self
    {
        $this->insuranceCents = $insuranceCents;

        return $this;
    }

    public function getDiscountRateBp(): ?int
    {
        return $this->discountRateBp;
    }

    public function setDiscountRateBp(?int $discountRateBp): self
    {
        $this->discountRateBp = $discountRateBp;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getProject(): ?string
    {
        return $this->project;
    }

    public function setProject(?string $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getIncoterms(): ?string
    {
        return $this->incoterms;
    }

    public function setIncoterms(?string $incoterms): self
    {
        $this->incoterms = $incoterms;

        return $this;
    }

    public function getDeliveryDate(): ?DateTimeImmutable
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(?DateTimeImmutable $deliveryDate): self
    {
        $this->deliveryDate = $deliveryDate;

        return $this;
    }

    public function getReverseCharge(): ?bool
    {
        return $this->reverseCharge;
    }

    public function setReverseCharge(?bool $reverseCharge): self
    {
        $this->reverseCharge = $reverseCharge;

        return $this;
    }

    public function getBankDetails(): ?string
    {
        return $this->bankDetails;
    }

    public function setBankDetails(?string $bankDetails): self
    {
        $this->bankDetails = $bankDetails;

        return $this;
    }

    public function getPurchaseOrderRef(): ?string
    {
        return $this->purchaseOrderRef;
    }

    public function setPurchaseOrderRef(?string $purchaseOrderRef): self
    {
        $this->purchaseOrderRef = $purchaseOrderRef;

        return $this;
    }

    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    public function setPaymentTerms(?string $paymentTerms): self
    {
        $this->paymentTerms = $paymentTerms;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getDocument(): ?MediaInterface
    {
        return $this->document;
    }

    public function setDocument(?MediaInterface $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getOcrJob(): ?OcrJobInterface
    {
        return $this->ocrJob;
    }

    public function setOcrJob(?OcrJobInterface $ocrJob): self
    {
        $this->ocrJob = $ocrJob;

        return $this;
    }

    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(InvoiceLineInterface $line): self
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setInvoice($this);
        }

        return $this;
    }

    public function removeLine(InvoiceLineInterface $line): self
    {
        if ($this->lines->removeElement($line) && $line->getInvoice() === $this) {
            $line->setInvoice(null);
        }

        return $this;
    }
}
