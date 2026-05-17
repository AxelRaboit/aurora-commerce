<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractOrder implements OrderInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 32)]
    protected string $number;

    #[ORM\Column(length: 64)]
    protected string $token;

    #[ORM\ManyToOne(targetEntity: CoreUserInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?CoreUserInterface $customer = null;

    #[ORM\Column(length: 16, enumType: OrderStatusEnum::class, options: ['default' => 'pending'])]
    protected OrderStatusEnum $status = OrderStatusEnum::Pending;

    #[ORM\Column(length: 180)]
    protected string $email;

    #[ORM\Column(length: 200)]
    protected string $name;

    #[ORM\Column(length: 200, nullable: true)]
    protected ?string $addressLine1 = null;

    #[ORM\Column(length: 200, nullable: true)]
    protected ?string $addressLine2 = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $city = null;

    #[ORM\Column(length: 20, nullable: true)]
    protected ?string $postalCode = null;

    #[ORM\Column(length: 2, nullable: true)]
    protected ?string $country = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $notes = null;

    #[ORM\Column]
    protected int $totalCents = 0;

    #[ORM\Column(length: 3, enumType: CurrencyEnum::class)]
    protected CurrencyEnum $currency = CurrencyEnum::EUR;

    #[ORM\Column(length: 64, nullable: true)]
    protected ?string $stripePaymentIntentId = null;

    #[ORM\Column(nullable: true)]
    protected ?int $refundedCents = null;

    #[ORM\Column(length: 5)]
    protected string $locale = 'fr';

    /** @var Collection<int, OrderLineInterface> */
    #[ORM\OneToMany(targetEntity: OrderLineInterface::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getCustomer(): ?CoreUserInterface
    {
        return $this->customer;
    }

    public function setCustomer(?CoreUserInterface $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getStatus(): OrderStatusEnum
    {
        return $this->status;
    }

    public function setStatus(OrderStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(?string $v): static
    {
        $this->addressLine1 = $v;

        return $this;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(?string $v): static
    {
        $this->addressLine2 = $v;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $v): static
    {
        $this->city = $v;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $v): static
    {
        $this->postalCode = $v;

        return $this;
    }

    public function getCountryEnum(): ?string
    {
        return $this->country;
    }

    public function setCountryEnum(?string $v): static
    {
        $this->country = $v;

        return $this;
    }

    public function requiresShipping(): bool
    {
        foreach ($this->lines as $line) {
            $listing = $line->getListing();
            if (null === $listing) {
                continue;
            }

            if ($listing->getProduct()->getType()->requiresShipping()) {
                return true;
            }
        }

        return false;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $v): static
    {
        $this->notes = $v;

        return $this;
    }

    public function getTotalCents(): int
    {
        return $this->totalCents;
    }

    public function setTotalCents(int $v): static
    {
        $this->totalCents = $v;

        return $this;
    }

    public function getCurrency(): CurrencyEnum
    {
        return $this->currency;
    }

    public function setCurrency(CurrencyEnum $v): static
    {
        $this->currency = $v;

        return $this;
    }

    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(OrderLineInterface $line): static
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setOrder($this);
        }

        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $id): static
    {
        $this->stripePaymentIntentId = $id;

        return $this;
    }

    public function getRefundedCents(): ?int
    {
        return $this->refundedCents;
    }

    public function setRefundedCents(?int $cents): static
    {
        $this->refundedCents = $cents;

        return $this;
    }

    public function isRefundable(): bool
    {
        return null !== $this->stripePaymentIntentId
            && OrderStatusEnum::Refunded !== $this->status
            && OrderStatusEnum::Pending !== $this->status
            && OrderStatusEnum::Cancelled !== $this->status;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }
}
