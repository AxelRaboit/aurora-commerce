<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\Ecommerce\Order\Contract\OrderManagerInterface;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Ecommerce\Order\Serializer\OrderSerializer;
use Aurora\Module\Ecommerce\Order\View\OrderDetailViewBuilder;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ecommerce/orders/{id}', name: 'ecommerce_orders', requirements: ['id' => '\d+|__id__'])]
#[IsGranted('ecommerce.orders.view')]
final class OrderDetailController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly OrderSerializer $orderSerializer,
        private readonly OrderManagerInterface $orderManager,
        private readonly OrderDetailViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '_show', methods: [HttpMethodEnum::Get->value])]
    public function show(Order $order): Response
    {
        return $this->render('@Ecommerce/admin/orders/show.html.twig', $this->viewBuilder->showView($order));
    }

    #[Route('/status', name: '_status', methods: [HttpMethodEnum::Patch->value])]
    #[IsGranted('ecommerce.orders.manage')]
    public function updateStatus(Order $order, Request $request): JsonResponse
    {
        $target = OrderStatusEnum::tryFrom((string) ($this->decodeJson($request)['status'] ?? ''));
        if (null === $target) {
            return $this->jsonFailure('Invalid status');
        }

        try {
            match ($target) {
                OrderStatusEnum::Paid => $this->orderManager->markPaid($order),
                OrderStatusEnum::Shipped => $this->orderManager->markShipped($order),
                OrderStatusEnum::Delivered => $this->orderManager->markDelivered($order),
                OrderStatusEnum::Cancelled => $this->orderManager->cancel($order),
                OrderStatusEnum::Pending => throw new InvalidArgumentException('Cannot revert an order to pending'),
            };
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->jsonSuccess(['order' => $this->orderSerializer->serialize($order)]);
    }
}
