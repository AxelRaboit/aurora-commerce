<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Payment\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Ecommerce\Order\Manager\OrderManagerInterface;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Aurora\Module\Ecommerce\Order\Service\OrderRefundService;
use Aurora\Module\Ecommerce\Payment\StripeService;
use Stripe\Charge;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly StripeService $stripeService,
        private readonly OrderRepository $orderRepository,
        private readonly OrderManagerInterface $orderManager,
        private readonly OrderRefundService $refundService,
    ) {}

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: [HttpMethodEnum::Post->value])]
    public function handle(Request $request): JsonResponse
    {
        $signature = $request->headers->get('Stripe-Signature', '');
        $payload = $request->getContent();

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);
        } catch (SignatureVerificationException) {
            return $this->jsonFailure('invalid_signature', HttpStatusEnum::BadRequest->value);
        }

        $object = $event->data->object;
        match (true) {
            'charge.refunded' === $event->type && $object instanceof Charge => $this->onChargeRefunded($object),
            'payment_intent.succeeded' === $event->type && $object instanceof PaymentIntent => $this->onPaymentIntentSucceeded($object),
            default => null,
        };

        return $this->jsonSuccess();
    }

    private function onChargeRefunded(Charge $charge): void
    {
        $paymentIntentId = $charge->payment_intent;
        if (null === $paymentIntentId) {
            return;
        }

        $order = $this->orderRepository->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);
        if (!$order instanceof Order || OrderStatusEnum::Refunded === $order->getStatus()) {
            return;
        }

        $this->refundService->markRefunded($order, $charge->amount_refunded);
    }

    private function onPaymentIntentSucceeded(PaymentIntent $paymentIntent): void
    {
        $order = $this->orderRepository->findOneBy(['stripePaymentIntentId' => $paymentIntent->id]);
        if (!$order instanceof Order || OrderStatusEnum::Pending !== $order->getStatus()) {
            return;
        }

        $this->orderManager->markPaid($order);
    }
}
