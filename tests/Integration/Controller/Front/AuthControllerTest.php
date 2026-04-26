<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Front;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Enum\UserStatusEnum;
use App\Enum\UserTypeEnum;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function testLoginPageRenders(): void
    {
        $this->client->request('GET', '/fr/login');
        self::assertResponseIsSuccessful();
    }

    public function testRegisterPageRendersWhenDisabled(): void
    {
        $this->client->request('GET', '/fr/register');
        self::assertResponseIsSuccessful();
    }

    public function testRegisterPageRendersWhenEnabled(): void
    {
        $this->setRegistrationEnabled(true);

        $this->client->request('GET', '/fr/register');
        self::assertResponseIsSuccessful();
    }

    public function testRegisterRejectsInvalidEmail(): void
    {
        $this->setRegistrationEnabled(true);

        $this->client->request('POST', '/fr/register', [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'verysecure123',
        ]);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Test User', (string) $this->client->getResponse()->getContent());
    }

    public function testRegisterCreatesUserAndRedirects(): void
    {
        $this->setRegistrationEnabled(true);

        $email = 'newfront-'.uniqid().'@velox.test';
        $this->client->request('POST', '/fr/register', [
            'name' => 'New Front User',
            'email' => $email,
            'password' => 'verysecure123',
        ]);

        self::assertResponseRedirects('/fr/register/confirm');

        $userRepository = static::getContainer()->get(UserRepository::class);
        $created = $userRepository->findOneBy(['email' => $email, 'type' => UserTypeEnum::FrontUser]);
        self::assertInstanceOf(User::class, $created);
        self::assertSame(UserStatusEnum::PendingVerification, $created->getStatus());
    }

    public function testRegisterConfirmPage(): void
    {
        $this->client->request('GET', '/fr/register/confirm');
        self::assertResponseIsSuccessful();
    }

    public function testForgotPasswordPage(): void
    {
        $this->client->request('GET', '/fr/forgot-password');
        self::assertResponseIsSuccessful();
    }

    public function testForgotPasswordSubmitNeverRevealsAccountExistence(): void
    {
        $this->client->request('POST', '/fr/forgot-password', ['email' => 'unknown@velox.test']);
        self::assertResponseIsSuccessful();
    }

    public function testResetPasswordPageWithInvalidToken(): void
    {
        $this->client->request('GET', '/fr/reset-password/invalid-selector/invalid-token');
        self::assertResponseIsSuccessful();
    }

    public function testVerifyEmailWithInvalidToken(): void
    {
        $this->client->request('GET', '/fr/verify-email/totally-bogus-token');
        self::assertResponseIsSuccessful();
    }

    public function testAccountRedirectsWhenAnonymous(): void
    {
        $this->client->request('GET', '/fr/account');
        self::assertResponseRedirects();
    }

    public function testAccountRendersForAuthenticatedFrontUser(): void
    {
        $user = $this->createFrontUser();
        $this->client->loginUser($user, 'front');

        $this->client->request('GET', '/fr/account');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString($user->getName(), (string) $this->client->getResponse()->getContent());
    }

    public function testLogoutRedirectsHome(): void
    {
        $user = $this->createFrontUser();
        $this->client->loginUser($user, 'front');

        $this->client->request('POST', '/fr/account/logout');
        self::assertResponseRedirects();
    }

    private function setRegistrationEnabled(bool $enabled): void
    {
        $settings = static::getContainer()->get(SettingRepository::class);
        $settings->set('front_registration_enabled', $enabled ? '1' : '0');
        static::getContainer()->get(EntityManagerInterface::class)->flush();
    }

    private function createFrontUser(): User
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setName('Front Tester');
        $user->setEmail('front-tester-'.uniqid().'@velox.test');
        $user->setType(UserTypeEnum::FrontUser);
        $user->setStatus(UserStatusEnum::Active);
        $user->setRoles([UserRoleEnum::User->value]);
        $user->setPassword($hasher->hashPassword($user, 'password'));

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
