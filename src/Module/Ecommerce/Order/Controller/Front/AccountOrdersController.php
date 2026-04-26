<?php

declare(strict_types=1);

namespace App\Module\Ecommerce\Order\Controller\Front;

use App\Core\Frontend\Controller\FrontLocaleTrait;
use App\Core\Frontend\Service\FrontContext;
use App\Core\Theme\Service\ThemeContext;
use App\Core\Theme\Service\ThemeResolver;
use App\Core\User\Entity\User;
use App\Module\Ecommerce\Order\Repository\OrderRepository;
use App\Module\Ecommerce\Order\Serializer\OrderSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccountOrdersController extends AbstractController
{
    use FrontLocaleTrait;

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderSerializer $orderSerializer,
        private readonly Security $security,
        private readonly FrontContext $frontContext,
        private readonly ThemeResolver $themeResolver,
        private readonly ThemeContext $themeContext,
    ) {}

    #[Route('/{locale}/account/orders', name: 'front_account_orders', requirements: ['locale' => '[a-z]{2}'], methods: ['GET'], priority: 8)]
    public function index(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('front_login', ['locale' => $locale]);
        }

        $page = max(1, (int) $request->query->get('page', '1'));
        $result = $this->orderRepository->findPaginatedForCustomer($user, $page, 20);

        return $this->render($this->themeResolver->resolve('account_orders'), [
            'orders' => array_map($this->orderSerializer->serialize(...), $result['items']),
            'pagination' => [
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
                'total' => $result['total'],
            ],
            'locale' => $locale,
            'context' => $this->frontContext,
            'themeContext' => $this->themeContext,
        ]);
    }
}
