<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\View;

use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Theme\Service\ThemeContext;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Aurora\Module\Ecommerce\Order\Serializer\OrderSerializerInterface;

/**
 * Builds the Twig payload for the customer account orders view.
 */
final readonly class AccountOrdersViewBuilder
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderSerializerInterface $orderSerializer,
        private FrontContext $frontContext,
        private ThemeContext $themeContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(User $user, int $page, string $locale): array
    {
        $result = $this->orderRepository->findPaginatedForCustomer($user, $page, 20);

        return [
            'orders' => array_map($this->orderSerializer->serialize(...), $result['items']),
            'pagination' => [
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
                'total' => $result['total'],
            ],
            'locale' => $locale,
            'context' => $this->frontContext,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
        ];
    }
}
