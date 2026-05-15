<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\Order\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Aurora\Module\Ecommerce\Order\View\OrdersViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrdersViewBuilderTest extends TestCase
{
    public function testIndexViewWithStatus(): void
    {
        $repo = $this->createStub(OrderRepository::class);
        $repo->method('countByStatus')->willReturn(['pending' => 5, 'paid' => 10]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnArgument(0);

        $pagination = new PaginationRequest(1, 20, 'jane');
        $view = (new OrdersViewBuilder($repo, $urlGenerator))->indexView($pagination, OrderStatusEnum::Paid, ['items' => []]);

        self::assertSame(['items' => []], $view['orders']);
        self::assertSame('jane', $view['search']);
        self::assertSame('paid', $view['currentStatus']);
        self::assertSame(['pending' => 5, 'paid' => 10], $view['stats']);
    }

    public function testIndexViewWithNoStatus(): void
    {
        $repo = $this->createStub(OrderRepository::class);
        $repo->method('countByStatus')->willReturn([]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/path');

        $pagination = new PaginationRequest(1, 20, null);
        $view = (new OrdersViewBuilder($repo, $urlGenerator))->indexView($pagination, null, []);

        self::assertSame('', $view['currentStatus']);
        self::assertSame('', $view['search']);
    }
}
