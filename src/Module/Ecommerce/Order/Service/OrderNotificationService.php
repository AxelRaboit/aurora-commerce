<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Service;

use Aurora\Core\Mail\Service\MailService;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class OrderNotificationService
{
    public function __construct(
        private MailService $mail,
        private SettingRepository $settingRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function notifyPaid(Order $order): void
    {
        $context = $this->buildContext($order);
        $this->mail->send($order->getEmail(), 'ecommerce.mail.subject.paid', '@Ecommerce/email/order_paid.html.twig', $context, locale: $order->getLocale());
        $this->mail->sendToAdmin('ecommerce.mail.subject.admin_new', '@Ecommerce/email/order_paid_admin.html.twig', $context + [
            'adminUrl' => $this->urlGenerator->generate('backend_ecommerce_orders_show', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    public function notifyShipped(Order $order): void
    {
        $this->mail->send($order->getEmail(), 'ecommerce.mail.subject.shipped', '@Ecommerce/email/order_shipped.html.twig', $this->buildContext($order), locale: $order->getLocale());
    }

    public function notifyCancelled(Order $order, bool $refunded = false): void
    {
        $this->mail->send($order->getEmail(), 'ecommerce.mail.subject.cancelled', '@Ecommerce/email/order_cancelled.html.twig', $this->buildContext($order) + ['refunded' => $refunded], locale: $order->getLocale());
    }

    public function notifyRefund(Order $order, int $amountCents, bool $isFullRefund): void
    {
        $this->mail->send($order->getEmail(), 'ecommerce.mail.subject.refund', '@Ecommerce/email/order_refunded.html.twig', $this->buildContext($order) + [
            'amountCents' => $amountCents,
            'isFullRefund' => $isFullRefund,
        ], locale: $order->getLocale());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(Order $order): array
    {
        return [
            'order' => $order,
            'currency' => $order->getCurrency(),
            'orderUrl' => $this->urlGenerator->generate('frontend_order_show', [
                'locale' => $this->settingRepository->getOrDefault(ApplicationParameterEnum::DefaultLocale),
                'token' => $order->getToken(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }
}
