<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Controller\Frontend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\LocaleTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Locale\Enum\CountryEnum;
use Aurora\Core\Theme\Service\ThemeResolver;
use Aurora\Core\User\Entity\User;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Ecommerce\Cart\Manager\CartManagerInterface;
use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Module\Ecommerce\Order\Dto\CheckoutInputFactoryInterface;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Ecommerce\Order\Manager\OrderManagerInterface;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Aurora\Module\Ecommerce\Order\View\CheckoutViewBuilder;
use Aurora\Module\Ecommerce\Payment\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CheckoutController extends AbstractController
{
    use LocaleTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly CartManagerInterface $cartManager,
        private readonly OrderManagerInterface $orderManager,
        private readonly OrderRepository $orderRepository,
        private readonly PayloadValidator $payloadValidator,
        private readonly Security $security,
        private readonly Context $context,
        private readonly ThemeResolver $themeResolver,
        private readonly CheckoutViewBuilder $viewBuilder,
        private readonly StripeService $stripeService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly CheckoutInputFactoryInterface $checkoutInputFactory,
    ) {}

    #[Route('/{locale}/checkout', name: 'frontend_checkout', requirements: ['locale' => '[a-z]{2}'], methods: [HttpMethodEnum::Get->value, HttpMethodEnum::Post->value], priority: 8)]
    public function checkout(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);
        $request->setLocale($locale);

        $cart = $this->cartManager->getCurrentCart();
        if (!$cart instanceof CartInterface || 0 === $cart->getItems()->count()) {
            return $this->redirectToRoute('frontend_cart', ['locale' => $locale]);
        }

        $cartRequiresShipping = $this->cartContainsPhysicalItem($cart);

        if ('POST' === $request->getMethod()) {
            return $this->handlePost($locale, $request, $cart, $cartRequiresShipping);
        }

        $formData = $this->initialFormData($cartRequiresShipping);
        $submitPath = $this->urlGenerator->generate('frontend_checkout', ['locale' => $locale]);

        return $this->render(
            $this->themeResolver->resolve('checkout'),
            $this->viewBuilder->checkoutView($cart, $cartRequiresShipping, $formData, $locale, $this->stripeService->getPublicKey(), $submitPath),
        );
    }

    private function handlePost(string $locale, Request $request, CartInterface $cart, bool $cartRequiresShipping): Response
    {
        $formData = $request->request->all();
        $input = $this->checkoutInputFactory->fromArray($formData);
        $errors = $this->payloadValidator->errors($input);
        if ($cartRequiresShipping) {
            $errors = array_merge($errors, $input->shippingErrors());
        }

        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $customer = $this->security->getUser() instanceof User ? $this->security->getUser() : null;

        try {
            $order = $this->orderManager->createFromCart($cart, $input, $customer, $locale);
            $paymentIntent = $this->stripeService->createPaymentIntent($order);

            $order->setStripePaymentIntentId($paymentIntent->id);
            $this->entityManager->flush();

            $returnUrl = $this->urlGenerator->generate('frontend_order_payment_return', [
                'locale' => $locale,
                'token' => $order->getToken(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->jsonSuccess([
                'clientSecret' => $paymentIntent->client_secret,
                'returnUrl' => $returnUrl,
            ]);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['stock' => $invalidArgumentException->getMessage()]);
        }
    }

    #[Route('/{locale}/order/{token}/payment-return', name: 'frontend_order_payment_return', requirements: ['locale' => '[a-z]{2}', 'token' => '[a-f0-9]{32}'], methods: [HttpMethodEnum::Get->value], priority: 8)]
    public function paymentReturn(string $locale, string $token, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);

        $order = $this->orderRepository->findOneByToken($token);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException();
        }

        $paymentIntentId = $request->query->get('payment_intent');

        if (null !== $paymentIntentId && OrderStatusEnum::Pending === $order->getStatus()) {
            try {
                $paymentIntent = $this->stripeService->retrievePaymentIntent($paymentIntentId);
                if ('succeeded' === $paymentIntent->status) {
                    $this->orderManager->markPaid($order);
                    $this->cartManager->clear();
                }
            } catch (Exception) {
                // Log silently — order stays Pending, admin can handle manually
            }
        }

        return $this->redirectToRoute('frontend_order_show', ['locale' => $locale, 'token' => $token]);
    }

    #[Route('/{locale}/order/{token}', name: 'frontend_order_show', requirements: ['locale' => '[a-z]{2}', 'token' => '[a-f0-9]{32}'], methods: [HttpMethodEnum::Get->value], priority: 8)]
    public function show(string $locale, string $token, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);
        $request->setLocale($locale);

        $order = $this->orderRepository->findOneByToken($token);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException();
        }

        return $this->render($this->themeResolver->resolve('order_show'), $this->viewBuilder->showView($order, $locale));
    }

    private function cartContainsPhysicalItem(CartInterface $cart): bool
    {
        foreach ($cart->getItems() as $item) {
            if ($item->getListing()->getProduct()->getType()->requiresShipping()) {
                return true;
            }
        }

        return false;
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
