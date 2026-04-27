<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration;

use Aurora\Core\DataFixtures\AppFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class IntegrationTestCase extends WebTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::bootKernel();
        $container = static::getContainer();

        $entityManager = $container->get(EntityManagerInterface::class);
        $fixtures = $container->get(AppFixtures::class);

        $executor = new ORMExecutor($entityManager, new ORMPurger($entityManager));
        $executor->execute([$fixtures]);

        static::ensureKernelShutdown();
    }
}
