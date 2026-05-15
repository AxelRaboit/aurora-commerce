<?php

declare(strict_types=1);

namespace Aurora\Core\Encryption\Doctrine;

use Aurora\Core\Encryption\Service\EncryptionServiceInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;
use Override;
use RuntimeException;

/**
 * Doctrine type that transparently encrypts/decrypts a text column using
 * Aurora's EncryptionService (libsodium XSalsa20-Poly1305).
 *
 * Usage on an entity property:
 *   #[ORM\Column(type: EncryptedTextType::NAME)]
 *   protected ?string $content = null;
 *
 * The encryption service is injected once at boot by EncryptedTypeBootstrapper.
 */
final class EncryptedTextType extends TextType
{
    public const string NAME = 'encrypted_text';

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
            throw new RuntimeException('EncryptedTextType used before EncryptionService was injected (boot order issue).');
        }

        return self::$encryptionService;
    }
}
