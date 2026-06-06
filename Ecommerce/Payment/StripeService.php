<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Payment;

use Aurora\Module\Ecommerce\Order\Entity\Order;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;

readonly class StripeService
{
    public function __construct(
        private string $secretKey,
        private string $publicKey,
        private string $webhookSecret,
    ) {
        Stripe::setApiKey($this->secretKey);
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @throws ApiErrorException
     */
    public function createPaymentIntent(Order $order): PaymentIntent
    {
        return PaymentIntent::create([
            'amount' => $order->getTotalCents(),
            'currency' => mb_strtolower($order->getCurrency()->value),
            'metadata' => [
                'order_number' => $order->getNumber(),
                'order_token' => $order->getToken(),
            ],
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * Create a refund. Pass null `$amountCents` for a full refund.
     *
     * @throws ApiErrorException
     */
    public function createRefund(string $paymentIntentId, ?int $amountCents = null): Refund
    {
        $params = ['payment_intent' => $paymentIntentId];
        if (null !== $amountCents) {
            $params['amount'] = $amountCents;
        }

        return Refund::create($params);
    }

    /**
     * Verifies the Stripe-Signature header and returns the parsed event.
     *
     * @throws SignatureVerificationException when the signature is invalid
     */
    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent($payload, $signature, $this->webhookSecret);
    }
}
