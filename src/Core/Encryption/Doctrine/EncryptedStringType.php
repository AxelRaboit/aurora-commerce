<?php

declare(strict_types=1);

namespace Aurora\Core\Encryption\Doctrine;

use Aurora\Core\Encryption\Service\EncryptionServiceInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Override;
use RuntimeException;

/**
 * Doctrine type that transparently encrypts/decrypts a short string column
 * using Aurora's EncryptionService.
 *
 * Note: the ciphertext is longer than the plaintext (nonce + tag + base64),
 * so the underlying column length must accommodate ~40 bytes overhead.
 * For free-form text prefer EncryptedTextType.
 */
final class EncryptedStringType extends StringType
{
    public const string NAME = 'encrypted_string';

    private static ?EncryptionServiceInterface $encryptionService = null;

    public static function setEncryptionService(EncryptionServiceInterface $service): void
    {
        self::$encryptionService = $service;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $this->service()->encrypt((string) $value);
    }

    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $this->service()->decrypt((string) $value);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    private function service(): EncryptionServiceInterface
    {
        if (!self::$encryptionService instanceof EncryptionServiceInterface) {
            throw new RuntimeException('EncryptedStringType used before EncryptionService was injected (boot order issue).');
        }

        return self::$encryptionService;
    }
}
