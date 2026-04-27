<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Locale\Enum\CountryEnum;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Ecommerce\Cart\Contract\CartManagerInterface;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Order\Contract\OrderManagerInterface;
use Aurora\Module\Ecommerce\Order\DTO\CheckoutInput;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class AdminOrdersControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'admin@aurora.app']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $this->order = $this->seedOrder();
    }

    public function testIndexReturnsListPayloadAsJson(): void
    {
        $this->client->request(
            'GET',
            '/admin/ecommerce/orders/list',
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $payload = json_decode($response->getContent(), true);
        self::assertTrue($payload['ok']);
        self::assertGreaterThanOrEqual(1, $payload['total']);
        self::assertSame($this->order->getNumber(), $payload['items'][0]['number']);
    }

    public function testFilterByStatusOnlyReturnsMatchingOrders(): void
    {
        $this->client->request('GET', '/admin/ecommerce/orders/list?status=cancelled');
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($payload['ok']);
        // Order is "paid" by default after checkout — cancelled bucket should be empty
        self::assertSame(0, $payload['total']);
    }

    public function testShowRendersOrderDetail(): void
    {
        $this->client->request('GET', sprintf('/admin/ecommerce/orders/%d', $this->order->getId()));
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString($this->order->getNumber(), $response->getContent());
    }

    public function testStatusTransitionPaidToShippedSucceeds(): void
    {
        $this->client->request(
            'PATCH',
            sprintf('/admin/ecommerce/orders/%d/status', $this->order->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'shipped']),
        );

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        self::assertTrue($payload['success']);
        self::assertSame('shipped', $payload['order']['status']);
    }

    public function testInvalidStatusReturns422(): void
    {
        // Cannot jump from paid → delivered without going through shipped first.
        // Actually delivered allows from shipped OR paid (per OrderManager). Use a truly invalid jump:
        // paid → pending is forbidden (cannot revert).
        $this->client->request(
            'PATCH',
            sprintf('/admin/ecommerce/orders/%d/status', $this->order->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'pending']),
        );

        self::assertSame(422, $this->client->getResponse()->getStatusCode());
    }

    private function seedOrder(): Order
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $cartManager = $container->get(CartManagerInterface::class);
        $orderManager = $container->get(OrderManagerInterface::class);

        // Inline seed (fixtures don't ship with shop products).
        $product = (new Product())
            ->setName('Test Product')
            ->setSku('TEST-'.bin2hex(random_bytes(4)))
            ->setPriceCents(1999)
            ->setCurrency(CurrencyEnum::EUR)
            ->setStatus(ProductStatusEnum::Active)
            ->setStockQuantity(100);
        $entityManager->persist($product);

        $listing = (new Listing())
            ->setProduct($product)
            ->setSlug('test-product-'.bin2hex(random_bytes(4)))
            ->setVisibleOnShop(true);
        $entityManager->persist($listing);
        $entityManager->flush();

        $cartManager->addItem($listing, 1);
        $cart = $cartManager->getCurrentCart(false);
        self::assertNotNull($cart);

        $input = new CheckoutInput(
            email: 'buyer@example.com',
            name: 'Buyer Name',
            addressLine1: '1 rue du Test',
            city: 'Paris',
            postalCode: '75001',
            country: CountryEnum::default()->value,
        );

        $order = $orderManager->checkout($cart, $input, null);
        self::assertSame(OrderStatusEnum::Paid, $order->getStatus());

        // Force fresh load to attach order to the test entity manager.
        return $container->get(OrderRepository::class)->find($order->getId());
    }
}
