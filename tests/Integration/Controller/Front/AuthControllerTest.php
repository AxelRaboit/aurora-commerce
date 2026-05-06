<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller\Front;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Enum\UserStatusEnum;
use Aurora\Core\User\Enum\UserTypeEnum;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AuthControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
    }

    public function testLoginPageRenders(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('frontend_login', ['locale' => 'fr']));
        self::assertResponseIsSuccessful();
    }

    public function testRegisterPageRendersWhenDisabled(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('frontend_register', ['locale' => 'fr']));
        self::assertResponseIsSuccessful();
    }

    public function testRegisterPageRendersWhenEnabled(): void
    {
        $this->setRegistrationEnabled(true);

        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('frontend_register', ['locale' => 'fr']));
        self::assertResponseIsSuccessful();
    }

    public function testRegisterRejectsInvalidEmail(): void
    {
        $this->setRegistrationEnabled(true);

        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('frontend_register', ['locale' => 'fr']), [
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

        $email = 'newfront-'.uniqid().'@aurora.test';
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('frontend_register', ['locale' => 'fr']), [
            'name' => 'New Front User',
            'email' => $email,
            'password' => 'verysecure123',
        ]);

        self::assertResponseRedirects($this->urlGenerator->generate('frontend_register_confirm', ['locale' => 'fr']));

        $userRepository = static::getContainer()->get(UserRepository::class);
        $created = $userRepository->findOneBy(['email' => $email, 'type' => UserTypeEnum::FrontUser]);
        self::assertInstanceOf(User::class, $created);
        self::assertSame(UserStatusEnum::PendingVerification, $created->getStatus());
    }

    public function testRegisterConfirmPage(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('frontend_register_confirm', ['locale' => 'fr']));
        self::assertResponseIsSuccessful();
    }

    public function testForgotPasswordPage(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('frontend_forgot_password', ['locale' => 'fr']));
        self::assertResponseIsSuccessful();
    }

    public function testForgotPasswordSubmitNeverRevealsAccountExistence(): void
    {
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('frontend_forgot_password', ['locale' => 'fr']), ['email' => 'unknown@aurora.test']);
        self::assertResponseIsSuccessful();
    }

    public function testResetPasswordPageWithInvalidToken(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('frontend_reset_password', ['locale' => 'fr', 'selector' => 'invalid-selector', 'token' => 'invalid-token']));
        self::assertResponseIsSuccessful();
    }

    public function testVerifyEmailWithInvalidToken(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('frontend_verify_email', ['locale' => 'fr', 'token' => 'totally-bogus-token']));
        self::assertResponseIsSuccessful();
    }

    public function testAccountRedirectsWhenAnonymous(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('frontend_account', ['locale' => 'fr']));
        self::assertResponseRedirects();
    }

    public function testAccountRendersForAuthenticatedFrontUser(): void
    {
        $user = $this->createFrontUser();
        $this->client->loginUser($user, 'front');

        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('frontend_account', ['locale' => 'fr']));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString($user->getName(), (string) $this->client->getResponse()->getContent());
    }

    public function testLogoutRedirectsHome(): void
    {
        $user = $this->createFrontUser();
        $this->client->loginUser($user, 'front');

        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('frontend_logout', ['locale' => 'fr']));
        self::assertResponseRedirects();
    }

    private function setRegistrationEnabled(bool $enabled): void
    {
        $settings = static::getContainer()->get(SettingRepository::class);
        $settings->set('frontend_registration_enabled', $enabled ? '1' : '0');
        static::getContainer()->get(EntityManagerInterface::class)->flush();
    }

    private function createFrontUser(): User
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setName('Front Tester');
        $user->setEmail('front-tester-'.uniqid().'@aurora.test');
        $user->setType(UserTypeEnum::FrontUser);
        $user->setStatus(UserStatusEnum::Active);
        $user->setRoles([UserRoleEnum::User->value]);
        $user->setPassword($hasher->hashPassword($user, 'password'));

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
