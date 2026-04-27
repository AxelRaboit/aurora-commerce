<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Serializer;

use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Entity\OrderLine;
use DateTimeInterface;

final readonly class OrderSerializer
{
    /** Compact projection for admin list rows — no lines, no addresses. */
    public function serializeForList(Order $order): array
    {
        $currency = $order->getCurrency();
        $totalCents = $order->getTotalCents();
        $customer = $order->getCustomer();

        return [
            'id' => $order->getId(),
            'number' => $order->getNumber(),
            'status' => $order->getStatus()->value,
            'email' => $order->getEmail(),
            'name' => $order->getName(),
            'totalCents' => $totalCents,
            'total' => $totalCents / (10 ** $currency->decimals()),
            'currency' => $currency->value,
            'currencySymbol' => $currency->symbol(),
            'itemCount' => $order->getLines()->count(),
            'customerId' => $customer?->getId(),
            'createdAt' => $order->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    public function serialize(Order $order): array
    {
        $currency = $order->getCurrency();
        $totalCents = $order->getTotalCents();

        $lines = [];
        foreach ($order->getLines() as $line) {
            $lines[] = $this->serializeLine($line);
        }

        return [
            'id' => $order->getId(),
            'number' => $order->getNumber(),
            'token' => $order->getToken(),
            'status' => $order->getStatus()->value,
            'email' => $order->getEmail(),
            'name' => $order->getName(),
            'addressLine1' => $order->getAddressLine1(),
            'addressLine2' => $order->getAddressLine2(),
            'city' => $order->getCity(),
            'postalCode' => $order->getPostalCode(),
            'country' => $order->getCountry(),
            'notes' => $order->getNotes(),
            'totalCents' => $totalCents,
            'total' => $totalCents / (10 ** $currency->decimals()),
            'currency' => $currency->value,
            'currencySymbol' => $currency->symbol(),
            'requiresShipping' => $order->requiresShipping(),
            'lines' => $lines,
            'createdAt' => $order->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    private function serializeLine(OrderLine $line): array
    {
        $currency = $line->getCurrency();

        return [
            'id' => $line->getId(),
            'title' => $line->getTitleSnapshot(),
            'sku' => $line->getSkuSnapshot(),
            'quantity' => $line->getQuantity(),
            'unitPrice' => $line->getUnitPriceCents() / (10 ** $currency->decimals()),
            'subtotal' => $line->getSubtotalCents() / (10 ** $currency->decimals()),
            'currencySymbol' => $currency->symbol(),
        ];
    }
}
