<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Controller\Front;

use Aurora\Core\Frontend\Controller\FrontLocaleTrait;
use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Locale\Enum\CountryEnum;
use Aurora\Core\Theme\Service\ThemeResolver;
use Aurora\Core\User\Entity\User;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Ecommerce\Cart\Contract\CartManagerInterface;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Order\Contract\OrderManagerInterface;
use Aurora\Module\Ecommerce\Order\DTO\CheckoutInput;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Aurora\Module\Ecommerce\Order\View\CheckoutViewBuilder;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController extends AbstractController
{
    use FrontLocaleTrait;

    public function __construct(
        private readonly CartManagerInterface $cartManager,
        private readonly OrderManagerInterface $orderManager,
        private readonly OrderRepository $orderRepository,
        private readonly PayloadValidator $payloadValidator,
        private readonly Security $security,
        private readonly FrontContext $frontContext,
        private readonly ThemeResolver $themeResolver,
        private readonly CheckoutViewBuilder $viewBuilder,
    ) {}

    #[Route('/{locale}/checkout', name: 'front_checkout', requirements: ['locale' => '[a-z]{2}'], methods: ['GET', 'POST'], priority: 8)]
    public function checkout(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $cart = $this->cartManager->getCurrentCart();
        if (0 === $cart->getItems()->count()) {
            return $this->redirectToRoute('front_cart', ['locale' => $locale]);
        }

        $cartRequiresShipping = $this->cartContainsPhysicalItem($cart);
        $errors = [];
        $stockError = null;
        $formData = $this->initialFormData($cartRequiresShipping);

        if ('POST' === $request->getMethod()) {
            $formData = array_merge($formData, $request->request->all());
            $input = CheckoutInput::fromArray($formData);
            $errors = $this->payloadValidator->errors($input);
            if ($cartRequiresShipping) {
                $errors = array_merge($errors, $input->shippingErrors());
            }

            if ([] === $errors) {
                $customer = $this->security->getUser() instanceof User ? $this->security->getUser() : null;
                try {
                    $order = $this->orderManager->checkout($cart, $input, $customer);

                    return $this->redirectToRoute('front_order_show', ['locale' => $locale, 'token' => $order->getToken()]);
                } catch (InvalidArgumentException $e) {
                    $stockError = $e->getMessage();
                }
            }
        }

        return $this->render($this->themeResolver->resolve('checkout'), $this->viewBuilder->checkoutView($cart, $errors, $stockError, $formData, $cartRequiresShipping, $locale));
    }

    private function cartContainsPhysicalItem(Cart $cart): bool
    {
        foreach ($cart->getItems() as $item) {
            if ($item->getListing()->getProduct()->getType()->requiresShipping()) {
                return true;
            }
        }

        return false;
    }

    #[Route('/{locale}/order/{token}', name: 'front_order_show', requirements: ['locale' => '[a-z]{2}', 'token' => '[a-f0-9]{32}'], methods: ['GET'], priority: 8)]
    public function show(string $locale, string $token, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $order = $this->orderRepository->findOneByToken($token);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException();
        }

        return $this->render($this->themeResolver->resolve('order_show'), $this->viewBuilder->showView($order, $locale));
    }

    private function initialFormData(bool $cartRequiresShipping): array
    {
        $user = $this->security->getUser();

        return [
            'email' => $user instanceof User ? $user->getEmail() : '',
            'name' => $user instanceof User ? $user->getName() : '',
            'addressLine1' => '',
            'addressLine2' => '',
            'city' => '',
            'postalCode' => '',
            'country' => $cartRequiresShipping ? CountryEnum::default()->value : '',
            'notes' => '',
        ];
    }
}
