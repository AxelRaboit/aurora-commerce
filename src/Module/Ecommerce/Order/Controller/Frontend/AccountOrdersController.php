<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Controller\Frontend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\LocaleTrait;
use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Theme\Service\ThemeResolver;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Ecommerce\Order\View\AccountOrdersViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccountOrdersController extends AbstractController
{
    use LocaleTrait;

    public function __construct(
        private readonly Security $security,
        private readonly Context $context,
        private readonly ThemeResolver $themeResolver,
        private readonly AccountOrdersViewBuilder $viewBuilder,
    ) {}

    #[Route('/{locale}/account/orders', name: 'frontend_account_orders', requirements: ['locale' => '[a-z]{2}'], methods: [HttpMethodEnum::Get->value], priority: 8)]
    public function index(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);
        $request->setLocale($locale);

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('frontend_login', ['locale' => $locale]);
        }

        $page = max(1, (int) $request->query->get('page', '1'));

        return $this->render($this->themeResolver->resolve('account_orders'), $this->viewBuilder->indexView($user, $page, $locale));
    }
}
