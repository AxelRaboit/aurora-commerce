<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Entity;

use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'billing_invoices')]
#[ORM\Index(name: 'idx_billing_invoice_status', columns: ['status'])]
#[ORM\Index(name: 'idx_billing_invoice_issued_at', columns: ['issued_at'])]
#[ORM\HasLifecycleCallbacks]
class Invoice
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $number = null;

    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Supplier $supplier = null;

    #[ORM\Column(length: 16, enumType: InvoiceStatusEnum::class, options: ['default' => 'draft'])]
    private InvoiceStatusEnum $status = InvoiceStatusEnum::Draft;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $issuedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $dueAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $paidAt = null;

    #[ORM\Column(length: 3, enumType: CurrencyEnum::class, options: ['default' => 'EUR'])]
    private CurrencyEnum $currency = CurrencyEnum::EUR;

    #[ORM\Column(nullable: true)]
    private ?int $totalNetCents = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalVatCents = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalGrossCents = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $purchaseOrderRef = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $buyerName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $buyerVatNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $buyerAddress = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $buyerCountryCode = null;

    /** The credit note (avoir) that cancels this invoice. Null if not cancelled. */
    #[ORM\OneToOne(targetEntity: self::class, inversedBy: 'cancelledInvoice')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $creditNote = null;

    /** When this invoice IS a credit note, points back to the invoice it cancels. */
    #[ORM\OneToOne(targetEntity: self::class, mappedBy: 'creditNote')]
    private ?self $cancelledInvoice = null;

    /** Original document (PDF/image) — owned by Core/Media. */
    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Media $document = null;

    /** Traceability link to the OCR job that produced this draft. */
    #[ORM\ManyToOne(targetEntity: OcrJob::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?OcrJob $ocrJob = null;

    /** @var Collection<int, InvoiceLine> */
    #[ORM\OneToMany(targetEntity: InvoiceLine::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    public function assertEditable(): void
    {
        if (!$this->status->isEditable()) {
            throw new InvalidArgumentException('admin.billing.invoices.update.locked');
        }
    }

    public function isCancelled(): bool
    {
        return $this->creditNote instanceof Invoice;
    }

    public function getCreditNote(): ?self
    {
        return $this->creditNote;
    }

    public function setCreditNote(?self $creditNote): self
    {
        $this->creditNote = $creditNote;

        return $this;
    }

    public function getCancelledInvoice(): ?self
    {
        return $this->cancelledInvoice;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): self
    {
        $this->supplier = $supplier;

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

    public function getBuyerName(): ?string
    {
        return $this->buyerName;
    }

    public function setBuyerName(?string $buyerName): self
    {
        $this->buyerName = $buyerName;

        return $this;
    }

    public function getBuyerVatNumber(): ?string
    {
        return $this->buyerVatNumber;
    }

    public function setBuyerVatNumber(?string $buyerVatNumber): self
    {
        $this->buyerVatNumber = $buyerVatNumber;

        return $this;
    }

    public function getBuyerAddress(): ?string
    {
        return $this->buyerAddress;
    }

    public function setBuyerAddress(?string $buyerAddress): self
    {
        $this->buyerAddress = $buyerAddress;

        return $this;
    }

    public function getBuyerCountryCode(): ?string
    {
        return $this->buyerCountryCode;
    }

    public function setBuyerCountryCode(?string $buyerCountryCode): self
    {
        $this->buyerCountryCode = $buyerCountryCode;

        return $this;
    }

    public function getDocument(): ?Media
    {
        return $this->document;
    }

    public function setDocument(?Media $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getOcrJob(): ?OcrJob
    {
        return $this->ocrJob;
    }

    public function setOcrJob(?OcrJob $ocrJob): self
    {
        $this->ocrJob = $ocrJob;

        return $this;
    }

    /** @return Collection<int, InvoiceLine> */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(InvoiceLine $line): self
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setInvoice($this);
        }

        return $this;
    }

    public function removeLine(InvoiceLine $line): self
    {
        if ($this->lines->removeElement($line) && $line->getInvoice() === $this) {
            $line->setInvoice(null);
        }

        return $this;
    }
}
