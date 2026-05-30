<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Core\Money\Enum\CurrencyEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\Collection;

interface OrderInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getNumber(): string;

    public function setNumber(string $number): static;

    public function getToken(): string;

    public function setToken(string $token): static;

    public function getCustomer(): ?CoreUserInterface;

    public function setCustomer(?CoreUserInterface $customer): static;

    public function getStatus(): OrderStatusEnum;

    public function setStatus(OrderStatusEnum $status): static;

    public function getEmail(): string;

    public function setEmail(string $email): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getAddressLine1(): ?string;

    public function setAddressLine1(?string $v): static;

    public function getAddressLine2(): ?string;

    public function setAddressLine2(?string $v): static;

    public function getCity(): ?string;

    public function setCity(?string $v): static;

    public function getPostalCode(): ?string;

    public function setPostalCode(?string $v): static;

    public function getCountryEnum(): ?string;

    public function setCountryEnum(?string $v): static;

    public function requiresShipping(): bool;

    public function getNotes(): ?string;

    public function setNotes(?string $v): static;

    public function getTotalCents(): int;

    public function setTotalCents(int $v): static;

    public function getCurrency(): CurrencyEnum;

    public function setCurrency(CurrencyEnum $v): static;

    /** @return Collection<int, OrderLineInterface> */
    public function getLines(): Collection;

    public function addLine(OrderLineInterface $line): static;

    public function getStripePaymentIntentId(): ?string;

    public function setStripePaymentIntentId(?string $id): static;

    public function getRefundedCents(): ?int;

    public function setRefundedCents(?int $cents): static;

    public function isRefundable(): bool;

    public function getLocale(): string;

    public function setLocale(string $locale): static;
}
