<?php

declare(strict_types=1);

namespace Aurora\Core\Encryption\Doctrine;

use Aurora\Core\Encryption\Service\EncryptionServiceInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
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

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return self::service()->encrypt((string) $value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return self::service()->decrypt((string) $value);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    private static function service(): EncryptionServiceInterface
    {
        if (null === self::$encryptionService) {
            throw new RuntimeException('EncryptedStringType used before EncryptionService was injected (boot order issue).');
        }

        return self::$encryptionService;
    }
}
