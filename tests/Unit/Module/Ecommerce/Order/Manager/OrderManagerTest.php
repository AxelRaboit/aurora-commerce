<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\Order\Manager;

use Aurora\Core\Contact\Event\ContactSignalEvent;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Entity\CartItem;
use Aurora\Module\Ecommerce\Cart\Manager\CartManagerInterface;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Order\Dto\CheckoutInputInterface;
use Aurora\Module\Ecommerce\Order\Entity\AbstractOrder;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Event\OrderCreatedEvent;
use Aurora\Module\Ecommerce\Order\Entity\OrderLine;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Ecommerce\Order\Manager\OrderManager;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Aurora\Module\Ecommerce\Order\Service\OrderNotificationService;
use Aurora\Module\Ecommerce\Order\Service\OrderRefundService;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Core\Money\Enum\CurrencyEnum;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AllowMockObjectsWithoutExpectations]
final class OrderManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private OrderRepository $orderRepository;
    private CartManagerInterface $cartManager;
    private OrderNotificationService $notificationService;
    private OrderRefundService $refundService;
    private SettingRepository $settingRepository;
    private EventDispatcherInterface $eventDispatcher;
    private OrderManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->orderRepository->method('getNextOrderNumber')->willReturn('ORD-000001');
        $this->cartManager = $this->createMock(CartManagerInterface::class);
        $this->notificationService = $this->createMock(OrderNotificationService::class);
        $this->refundService = $this->createMock(OrderRefundService::class);
        $this->settingRepository = $this->createMock(SettingRepository::class);
        $this->settingRepository->method('getOrDefault')->willReturn('ORD');
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // wrapInTransaction must invoke its callback synchronously and
        // return its result — otherwise the transactional methods
        // (markPaid, cancel, checkout) are dead-code at the test level.
        $em = $this->entityManager;
        $this->entityManager->method('wrapInTransaction')->willReturnCallback(
            static fn (callable $fn) => $fn($em),
        );

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $auditLogger = new AuditLogger(
            $this->entityManager,
            $security,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );

        $result = $this->createStub(Result::class);
        $result->method('fetchOne')->willReturn(1);
        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        $this->manager = new OrderManager(
            $this->entityManager,
            $this->orderRepository,
            $this->cartManager,
            $auditLogger,
            $this->notificationService,
            $this->refundService,
            $this->settingRepository,
            new SequenceGenerator($connection),
            $this->eventDispatcher,
        );
    }

    public function testCreateFromCartRefusesEmptyCart(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->manager->createFromCart($this->makeCart(), $this->makeCheckoutInput(), null, 'fr');
    }

    public function testCreateFromCartRefusesWhenAnyItemIsOutOfStock(): void
    {
        // Stock validation is upfront, BEFORE any persistence — so a
        // cart with mixed in-stock/out-of-stock items rejects with no
        // partial Order row left in DB.
        $cart = $this->makeCart();
        $product = $this->makeProduct(stock: 0);
        $cart->addItem($this->makeCartItem($this->makeListing(product: $product), quantity: 1));

        $this->entityManager->expects(self::never())->method('persist');

        $this->expectException(InvalidArgumentException::class);

        $this->manager->createFromCart($cart, $this->makeCheckoutInput(), null, 'fr');
    }

    public function testCreateFromCartSnapshotsLineItems(): void
    {
        // Critical money-flow guarantee: every Order line gets its own
        // SNAPSHOT of title / reference / price / currency at order
        // time. Later product edits never retroactively change a paid
        // order's amounts (legal + auditability requirement).
        $product = $this->makeProduct(stock: 100, name: 'Widget', reference: 'WGT-1');
        $listing = $this->makeListing(product: $product, title: 'Widget — Blue');

        $cart = $this->makeCart();
        $cart->addItem($this->makeCartItem($listing, quantity: 3, unitPriceCents: 1250));

        $order = $this->manager->createFromCart($cart, $this->makeCheckoutInput(email: 'buyer@example.com'), null, 'fr');

        self::assertCount(1, $order->getLines());
        $line = $order->getLines()->first();
        self::assertSame('Widget — Blue', $line->getTitleSnapshot());
        self::assertSame('WGT-1', $line->getReferenceSnapshot());
        self::assertSame(3, $line->getQuantity());
        self::assertSame(1250, $line->getUnitPriceCents());
        self::assertSame(CurrencyEnum::EUR, $line->getCurrency());
    }

    public function testCreateFromCartComputesTotalFromLineSubtotals(): void
    {
        // Total = Σ (line.unitPriceCents × line.quantity). Verified
        // against multiple lines with different prices.
        $cart = $this->makeCart();
        $cart->addItem($this->makeCartItem($this->makeListing(product: $this->makeProduct()), quantity: 2, unitPriceCents: 1000));
        $cart->addItem($this->makeCartItem($this->makeListing(product: $this->makeProduct()), quantity: 3, unitPriceCents: 500));

        $order = $this->manager->createFromCart($cart, $this->makeCheckoutInput(), null, 'fr');

        // 2*1000 + 3*500 = 2000 + 1500 = 3500
        self::assertSame(3500, $order->getTotalCents());
        self::assertSame(CurrencyEnum::EUR, $order->getCurrency());
    }

    public function testCreateFromCartStampsOrderMetadata(): void
    {
        // Order number from repository sequence, token = secure-random,
        // status = Pending, customer + locale + checkout address.
        $cart = $this->makeCart();
        $cart->addItem($this->makeCartItem($this->makeListing(product: $this->makeProduct()), quantity: 1));

        $order = $this->manager->createFromCart(
            $cart,
            $this->makeCheckoutInput(email: 'buyer@x.com', name: 'Alice', city: 'Paris'),
            null,
            'fr',
        );

        self::assertSame('ORD-000001', $order->getNumber());
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $order->getToken(), '32-hex token = 16 bytes secure-random');
        self::assertSame(OrderStatusEnum::Pending, $order->getStatus());
        self::assertSame('buyer@x.com', $order->getEmail());
        self::assertSame('Alice', $order->getName());
        self::assertSame('Paris', $order->getCity());
        self::assertSame('fr', $order->getLocale());
    }

    public function testCreateFromCartDispatchesOrderCreatedAndContactSignalEvents(): void
    {
        $cart = $this->makeCart();
        $cart->addItem($this->makeCartItem($this->makeListing(product: $this->makeProduct()), quantity: 1));

        $dispatched = [];
        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$dispatched): object {
                $dispatched[] = $event;

                return $event;
            });

        $this->manager->createFromCart(
            $cart,
            $this->makeCheckoutInput(email: 'buyer@x.com', name: 'Alice'),
            null,
            'fr',
        );

        // Ecommerce's own event first, then the decoupled cross-module signal
        // a CRM (if installed) listens to — carries the buyer's contact data.
        self::assertInstanceOf(OrderCreatedEvent::class, $dispatched[0]);
        self::assertInstanceOf(ContactSignalEvent::class, $dispatched[1]);
        self::assertSame('buyer@x.com', $dispatched[1]->getEmail());
        self::assertSame('Alice', $dispatched[1]->getFullName());
        self::assertSame('order', $dispatched[1]->getSourceKey());
        self::assertSame(['client'], $dispatched[1]->getTagSlugs());
    }

    public function testMarkPaidIsNoopWhenOrderNotPending(): void
    {
        $order = $this->makeOrder(status: OrderStatusEnum::Paid);

        $this->entityManager->expects(self::never())->method('wrapInTransaction');
        $this->notificationService->expects(self::never())->method('notifyPaid');

        $this->manager->markPaid($order);
    }

    public function testMarkPaidTransitionsAndNotifies(): void
    {
        // Pending → Paid: decrements stock via Doctrine find(LOCK), sets
        // status, audits, notifies.
        $product = $this->makeProduct(id: 7, stock: 10);
        $order = $this->makeOrder();
        $order->addLine($this->makeOrderLine($this->makeListing(product: $product), quantity: 4));

        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(Product::class, 7, LockMode::PESSIMISTIC_WRITE)
            ->willReturn($product);

        $this->notificationService->expects(self::once())->method('notifyPaid')->with($order);

        $this->manager->markPaid($order);

        self::assertSame(OrderStatusEnum::Paid, $order->getStatus());
        self::assertSame(6, $product->getStockQuantity(), '10 − 4 = 6 stock left after decrement');
    }

    public function testMarkPaidRefusesWhenStockNoLongerAvailable(): void
    {
        // Stock validation re-runs under the pessimistic lock — if
        // someone else's checkout snuck through in between, this
        // markPaid bails (and the wrapped transaction rolls back).
        $product = $this->makeProduct(id: 7, stock: 1);
        $order = $this->makeOrder();
        $order->addLine($this->makeOrderLine($this->makeListing(product: $product), quantity: 5));

        $this->entityManager->method('find')->willReturn($product);

        $this->expectException(InvalidArgumentException::class);

        $this->manager->markPaid($order);
    }

    public function testMarkShippedRequiresPaidStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->manager->markShipped($this->makeOrder(status: OrderStatusEnum::Pending));
    }

    public function testMarkShippedFromPaidTransitionsAndNotifies(): void
    {
        $order = $this->makeOrder(status: OrderStatusEnum::Paid);

        $this->notificationService->expects(self::once())->method('notifyShipped')->with($order);

        $this->manager->markShipped($order);

        self::assertSame(OrderStatusEnum::Shipped, $order->getStatus());
    }

    public function testMarkDeliveredAcceptsPaidOrShipped(): void
    {
        // Skip-shipping shop (digital goods?) — orders can go Paid →
        // Delivered without passing through Shipped.
        $order = $this->makeOrder(status: OrderStatusEnum::Paid);
        $this->manager->markDelivered($order);
        self::assertSame(OrderStatusEnum::Delivered, $order->getStatus());

        $order2 = $this->makeOrder(status: OrderStatusEnum::Shipped);
        $this->manager->markDelivered($order2);
        self::assertSame(OrderStatusEnum::Delivered, $order2->getStatus());
    }

    public function testTransitionToSameStatusIsNoop(): void
    {
        $order = $this->makeOrder(status: OrderStatusEnum::Shipped);

        // Re-marking Shipped: no flush, no audit, no notification.
        $this->entityManager->expects(self::never())->method('flush');

        $this->manager->markShipped($order);
    }

    public function testCancelNoopOnAlreadyCancelledOrDelivered(): void
    {
        // Idempotent + finality: Cancelled and Delivered are terminal.
        $this->refundService->expects(self::never())->method('refundForCancel');
        $this->notificationService->expects(self::never())->method('notifyCancelled');

        $this->manager->cancel($this->makeOrder(status: OrderStatusEnum::Cancelled));
        $this->manager->cancel($this->makeOrder(status: OrderStatusEnum::Delivered));
    }

    public function testCancelTriggersStripeRefundWhenPaidWithPaymentIntent(): void
    {
        // Paid + has Stripe payment intent + not already refunded
        // → call refundService → restock → cancel.
        $product = $this->makeProduct(id: 5, stock: 10);
        $order = $this->makeOrder(status: OrderStatusEnum::Paid);
        $order->setStripePaymentIntentId('pi_test_abc');
        $order->addLine($this->makeOrderLine($this->makeListing(product: $product), quantity: 3));

        $this->refundService->expects(self::once())->method('refundForCancel')->with($order);
        $this->entityManager->method('find')->willReturn($product);
        $this->notificationService->expects(self::once())
            ->method('notifyCancelled')
            ->with($order, true);

        $this->manager->cancel($order);

        self::assertSame(OrderStatusEnum::Cancelled, $order->getStatus());
        self::assertSame(13, $product->getStockQuantity(), '10 + 3 restocked');
    }

    public function testCancelSkipsRefundWhenNoStripePaymentIntent(): void
    {
        // Paid manually (cash, transfer, free order) — no Stripe to
        // refund, but still restock.
        $product = $this->makeProduct(id: 5, stock: 10);
        $order = $this->makeOrder(status: OrderStatusEnum::Paid);
        $order->setStripePaymentIntentId(null);
        $order->addLine($this->makeOrderLine($this->makeListing(product: $product), quantity: 2));

        $this->refundService->expects(self::never())->method('refundForCancel');
        $this->entityManager->method('find')->willReturn($product);
        $this->notificationService->expects(self::once())
            ->method('notifyCancelled')
            ->with($order, false);

        $this->manager->cancel($order);

        self::assertSame(12, $product->getStockQuantity());
    }

    public function testCancelSkipsRefundAndRestockWhenAlreadyRefunded(): void
    {
        // The Refund flow already restocked + refunded. cancel() on a
        // Refunded order just transitions to Cancelled, no double-
        // restock (would inflate inventory).
        $product = $this->makeProduct(id: 5, stock: 10);
        $order = $this->makeOrder(status: OrderStatusEnum::Refunded);
        $order->setStripePaymentIntentId('pi_test');
        $order->addLine($this->makeOrderLine($this->makeListing(product: $product), quantity: 4));

        $this->refundService->expects(self::never())->method('refundForCancel');
        $this->entityManager->expects(self::never())->method('find');

        $this->manager->cancel($order);

        self::assertSame(10, $product->getStockQuantity(), 'no restock — refund already did it');
        self::assertSame(OrderStatusEnum::Cancelled, $order->getStatus());
    }

    public function testCheckoutRefusesEmptyCart(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->manager->checkout($this->makeCart(), $this->makeCheckoutInput(), null, 'fr');
    }

    public function testCheckoutClearsCartAfterSuccessfulOrder(): void
    {
        // End-to-end happy path: lock stock, create order, mark paid
        // (auto-stubbed for MVP), then clear the cart so the user
        // doesn't see stale items if they navigate back.
        $product = $this->makeProduct(id: 1, stock: 10);
        $cart = $this->makeCart();
        $cart->addItem($this->makeCartItem($this->makeListing(product: $product), quantity: 2));

        $this->entityManager->method('find')->willReturn($product);
        $this->cartManager->expects(self::once())->method('clear');

        $order = $this->manager->checkout($cart, $this->makeCheckoutInput(), null, 'fr');

        self::assertSame(OrderStatusEnum::Paid, $order->getStatus(), 'auto-paid stub for MVP');
    }

    // ── Fixtures ────────────────────────────────────────────────────

    private function makeCheckoutInput(
        string $email = 'buyer@example.com',
        string $name = 'Buyer',
        ?string $city = null,
    ): CheckoutInputInterface {
        $input = $this->createStub(CheckoutInputInterface::class);
        $input->method('getEmail')->willReturn($email);
        $input->method('getName')->willReturn($name);
        $input->method('getAddressLine1')->willReturn(null);
        $input->method('getAddressLine2')->willReturn(null);
        $input->method('getCity')->willReturn($city);
        $input->method('getPostalCode')->willReturn(null);
        $input->method('getCountry')->willReturn(null);
        $input->method('getNotes')->willReturn(null);

        return $input;
    }

    private function makeCart(): Cart
    {
        $cart = new Cart();
        (new ReflectionProperty(Cart::class, 'id'))->setValue($cart, 1);

        return $cart;
    }

    private function makeCartItem(Listing $listing, int $quantity, int $unitPriceCents = 1000, CurrencyEnum $currency = CurrencyEnum::EUR): CartItem
    {
        $item = new CartItem();
        $item->setListing($listing);
        $item->setQuantity($quantity);
        $item->setUnitPriceCents($unitPriceCents);
        $item->setCurrency($currency);

        return $item;
    }

    private function makeProduct(int $id = 1, ?int $stock = null, string $name = 'P', string $reference = 'P-REF'): Product
    {
        $product = new Product();
        (new ReflectionProperty(Product::class, 'id'))->setValue($product, $id);
        $product->setName($name);
        $product->setReference($reference);
        $product->setPriceCents(1000);
        $product->setCurrency(CurrencyEnum::EUR);
        $product->setStockQuantity($stock);

        return $product;
    }

    private function makeListing(Product $product, ?string $title = null): Listing
    {
        $listing = new Listing();
        (new ReflectionProperty(Listing::class, 'id'))->setValue($listing, $product->getId());
        $listing->setProduct($product);
        if (null !== $title) {
            // `getDisplayTitle()` returns marketingTitle ?? product.name.
            // Tests that care about a specific title set it here; otherwise
            // assertions fall through to the product name.
            $listing->setMarketingTitle($title);
        }

        return $listing;
    }

    private function makeOrderLine(Listing $listing, int $quantity, int $unitPriceCents = 1000): OrderLine
    {
        $line = new OrderLine();
        $line->setListing($listing);
        $line->setTitleSnapshot('snap');
        $line->setReferenceSnapshot('REF');
        $line->setQuantity($quantity);
        $line->setUnitPriceCents($unitPriceCents);
        $line->setCurrency(CurrencyEnum::EUR);

        return $line;
    }

    private function makeOrder(OrderStatusEnum $status = OrderStatusEnum::Pending): Order
    {
        $order = new Order();
        (new ReflectionProperty(Order::class, 'id'))->setValue($order, 1);
        $order->setNumber('ORD-000001');
        $order->setToken('token');
        $order->setStatus($status);
        $order->setEmail('e@x.com');
        $order->setName('N');
        $order->setLocale('fr');
        $order->setTotalCents(1000);
        $order->setCurrency(CurrencyEnum::EUR);

        $now = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        (new ReflectionProperty(AbstractOrder::class, 'createdAt'))->setValue($order, $now);
        (new ReflectionProperty(AbstractOrder::class, 'updatedAt'))->setValue($order, $now);

        return $order;
    }
}
