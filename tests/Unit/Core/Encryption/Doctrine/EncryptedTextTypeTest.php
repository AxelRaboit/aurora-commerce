<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Encryption\Doctrine;

use Aurora\Core\Encryption\Doctrine\EncryptedTextType;
use Aurora\Core\Encryption\Service\EncryptionService;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EncryptedTextTypeTest extends TestCase
{
    private EncryptedTextType $type;

    protected function setUp(): void
    {
        if (!Type::hasType(EncryptedTextType::NAME)) {
            Type::addType(EncryptedTextType::NAME, EncryptedTextType::class);
        }

        /** @var EncryptedTextType $type */
        $type = Type::getType(EncryptedTextType::NAME);
        $this->type = $type;

        $key = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
        EncryptedTextType::setEncryptionService(new EncryptionService($key));
    }

    public function testRoundtripThroughDatabaseValueAndPhpValue(): void
    {
        $platform = new PostgreSQLPlatform();
        $plaintext = "Multi-line\ncontent with **markdown** and accents éàü.";

        $stored = $this->type->convertToDatabaseValue($plaintext, $platform);
        self::assertNotNull($stored);
        self::assertNotSame($plaintext, $stored, 'value must be transformed before storage');

        $loaded = $this->type->convertToPHPValue($stored, $platform);
        self::assertSame($plaintext, $loaded);
    }

    public function testNullValuesPassThrough(): void
    {
        $platform = new PostgreSQLPlatform();

        self::assertNull($this->type->convertToDatabaseValue(null, $platform));
        self::assertNull($this->type->convertToPHPValue(null, $platform));
    }

    public function testThrowsWhenServiceNotInjected(): void
    {
        // Reset the static service via reflection to simulate boot-order issue.
        $reflection = new \ReflectionClass(EncryptedTextType::class);
        $property = $reflection->getProperty('encryptionService');
        $property->setValue(null, null);

        $this->expectException(RuntimeException::class);
        $this->type->convertToDatabaseValue('some text', new PostgreSQLPlatform());
    }
}
