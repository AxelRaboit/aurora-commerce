<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class ProfileMoodTest extends IntegrationTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'admin@aurora.app']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');
    }

    public function testSavingValidMessagePersistsAndReturnsIt(): void
    {
        [$status, $body] = $this->postJson('/admin/profile/mood', ['moodMessage' => 'Shipping things ✨']);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame('Shipping things ✨', $body['moodMessage']);
        self::assertSame('Shipping things ✨', $this->reloadAdmin()->getMoodMessage());
    }

    public function testEmptyStringClearsTheMoodMessage(): void
    {
        $this->postJson('/admin/profile/mood', ['moodMessage' => 'set']);
        self::assertSame('set', $this->reloadAdmin()->getMoodMessage());

        [$status, $body] = $this->postJson('/admin/profile/mood', ['moodMessage' => '   ']);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertNull($body['moodMessage']);
        self::assertNull($this->reloadAdmin()->getMoodMessage());
    }

    public function testMessageOver160CharsIsRejected(): void
    {
        $tooLong = str_repeat('a', User::MOOD_MESSAGE_MAX_LENGTH + 1);

        [$status, $body] = $this->postJson('/admin/profile/mood', ['moodMessage' => $tooLong]);

        self::assertSame(200, $status);
        self::assertFalse($body['success']);
        self::assertArrayHasKey('moodMessage', $body['errors']);
        self::assertNull($this->reloadAdmin()->getMoodMessage());
    }

    public function testMessageAtExactly160CharsIsAccepted(): void
    {
        $exact = str_repeat('a', User::MOOD_MESSAGE_MAX_LENGTH);

        [$status, $body] = $this->postJson('/admin/profile/mood', ['moodMessage' => $exact]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame($exact, $this->reloadAdmin()->getMoodMessage());
    }

    public function testEntitySetterRejectsOverlongValue(): void
    {
        $user = new User();

        $this->expectException(InvalidArgumentException::class);
        $user->setMoodMessage(str_repeat('a', User::MOOD_MESSAGE_MAX_LENGTH + 1));
    }

    private function reloadAdmin(): User
    {
        $repository = static::getContainer()->get(UserRepository::class);
        // Clear identity map to force a fresh read after the request.
        $repository->getEntityManager()->clear();
        $admin = $repository->findOneBy(['email' => 'admin@aurora.app']);
        self::assertInstanceOf(User::class, $admin);

        return $admin;
    }

    /** @return array{0: int, 1: array} */
    private function postJson(string $url, array $payload): array
    {
        $this->client->request('POST', $url, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        return [
            $this->client->getResponse()->getStatusCode(),
            json_decode((string) $this->client->getResponse()->getContent(), true) ?? [],
        ];
    }
}
