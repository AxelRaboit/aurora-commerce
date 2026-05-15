<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Encryption\Service;

use Aurora\Core\Encryption\Service\EncryptionService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EncryptionServiceTest extends TestCase
{
    private function makeService(): EncryptionService
    {
        $key = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

        return new EncryptionService($key);
    }

    public function testConstructorThrowsOnInvalidBase64(): void
    {
        $this->expectException(RuntimeException::class);

        new EncryptionService('not-base64!!!');
    }

    public function testConstructorThrowsOnWrongKeySize(): void
    {
        $this->expectException(RuntimeException::class);

        new EncryptionService(base64_encode('too-short'));
    }

    public function testEncryptAndDecryptRoundtrip(): void
    {
        $service = $this->makeService();
        $plaintext = "# Markdown title\n\nSome **content** with `code` and [[wiki-link]].";

        $encrypted = $service->encrypt($plaintext);
        $decrypted = $service->decrypt($encrypted);

        self::assertSame($plaintext, $decrypted);
    }

    public function testEncryptProducesDifferentCiphertextEachTime(): void
    {
        $service = $this->makeService();
        $plaintext = 'same-input';

        $first = $service->encrypt($plaintext);
        $second = $service->encrypt($plaintext);

        self::assertNotSame($first, $second, 'nonce should make outputs different');
        self::assertSame($plaintext, $service->decrypt($first));
        self::assertSame($plaintext, $service->decrypt($second));
    }

    public function testDecryptReturnsNullForInvalidBase64(): void
    {
        $service = $this->makeService();

        self::assertNull($service->decrypt('not-valid-base64!!!'));
    }

    public function testDecryptReturnsNullForTooShortData(): void
    {
        $service = $this->makeService();

        self::assertNull($service->decrypt(base64_encode('short')));
    }

    public function testDecryptReturnsNullForTamperedCiphertext(): void
    {
        $service = $this->makeService();
        $plaintext = 'secret';

        $encrypted = $service->encrypt($plaintext);
        $decoded = base64_decode($encrypted, strict: true);
        $tampered = base64_encode(mb_substr($decoded, 0, -1).chr((ord($decoded[-1]) + 1) % 256));

        self::assertNull($service->decrypt($tampered));
    }
}
