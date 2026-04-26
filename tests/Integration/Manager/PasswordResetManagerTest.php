<?php

declare(strict_types=1);

namespace App\Tests\Integration\Manager;

use App\Core\Auth\Entity\ResetPasswordRequest;
use App\Core\Auth\Manager\PasswordResetManager;
use App\Core\User\Entity\User;
use App\Core\User\Enum\UserStatusEnum;
use App\Core\User\Enum\UserTypeEnum;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetManagerTest extends IntegrationTestCase
{
    private PasswordResetManager $manager;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
        $this->manager = static::getContainer()->get(PasswordResetManager::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateRequestForUserReturnsTokenAndExpiry(): void
    {
        $user = $this->createUser(UserTypeEnum::FrontUser);

        $result = $this->manager->createRequestForUser($user);

        self::assertArrayHasKey('selector', $result);
        self::assertArrayHasKey('plainToken', $result);
        self::assertArrayHasKey('expiresAt', $result);
        self::assertNotEmpty($result['selector']);
        self::assertNotEmpty($result['plainToken']);
    }

    public function testValidateTokenAcceptsValidToken(): void
    {
        $user = $this->createUser(UserTypeEnum::FrontUser);
        $request = $this->manager->createRequestForUser($user);

        $resolved = $this->manager->validateToken($request['selector'], $request['plainToken'], UserTypeEnum::FrontUser);

        self::assertInstanceOf(ResetPasswordRequest::class, $resolved);
        self::assertSame($user->getId(), $resolved->getUser()->getId());
    }

    public function testValidateTokenRejectsWrongToken(): void
    {
        $user = $this->createUser(UserTypeEnum::FrontUser);
        $request = $this->manager->createRequestForUser($user);

        self::assertNull($this->manager->validateToken($request['selector'], 'wrong-token'));
    }

    public function testValidateTokenRejectsWrongUserType(): void
    {
        $user = $this->createUser(UserTypeEnum::FrontUser);
        $request = $this->manager->createRequestForUser($user);

        self::assertNull($this->manager->validateToken($request['selector'], $request['plainToken'], UserTypeEnum::Admin));
    }

    public function testResetPasswordUpdatesHashAndConsumesRequest(): void
    {
        $user = $this->createUser(UserTypeEnum::FrontUser);
        $oldHash = $user->getPassword();
        $request = $this->manager->createRequestForUser($user);
        $resetRequest = $this->manager->validateToken($request['selector'], $request['plainToken'], UserTypeEnum::FrontUser);
        self::assertNotNull($resetRequest);

        $this->manager->resetPassword($resetRequest, 'brandnewpassword');

        self::assertNotSame($oldHash, $user->getPassword());
        self::assertNull($this->manager->validateToken($request['selector'], $request['plainToken'], UserTypeEnum::FrontUser));
    }

    public function testCreateRequestReplacesPreviousOne(): void
    {
        $user = $this->createUser(UserTypeEnum::FrontUser);
        $first = $this->manager->createRequestForUser($user);
        $second = $this->manager->createRequestForUser($user);

        self::assertNull($this->manager->validateToken($first['selector'], $first['plainToken'], UserTypeEnum::FrontUser));
        self::assertInstanceOf(ResetPasswordRequest::class, $this->manager->validateToken($second['selector'], $second['plainToken'], UserTypeEnum::FrontUser));
    }

    private function createUser(UserTypeEnum $type): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setName('Reset Tester');
        $user->setEmail('reset-'.uniqid().'@velox.test');
        $user->setType($type);
        $user->setStatus(UserStatusEnum::Active);
        $user->setPassword($hasher->hashPassword($user, 'oldpassword'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
