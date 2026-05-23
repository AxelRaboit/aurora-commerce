<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Attachment\Enum;

/**
 * Mime types accepted as a receipt attachment on a personal-finance
 * transaction. Self-contained on purpose: PF does not require the
 * Media module to be installed and may evolve its accepted-type list
 * independently from Aurora's broader media catalog.
 */
enum PersonalFinanceAttachmentMimeTypeEnum: string
{
    case Jpeg = 'image/jpeg';
    case Jpg = 'image/jpg';
    case Png = 'image/png';
    case Webp = 'image/webp';
    case Pdf = 'application/pdf';

    /**
     * Canonical filesystem extension (no leading dot) — used when an
     * uploaded receipt is renamed to a UUID on disk and the client's
     * original filename is dropped.
     */
    public function extension(): string
    {
        return match ($this) {
            self::Jpeg, self::Jpg => 'jpg',
            self::Png => 'png',
            self::Webp => 'webp',
            self::Pdf => 'pdf',
        };
    }
}
