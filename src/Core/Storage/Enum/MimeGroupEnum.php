<?php

declare(strict_types=1);

namespace Aurora\Core\Storage\Enum;

use Doctrine\ORM\QueryBuilder;

/**
 * Coarse-grained file type buckets, the same shape the Media module uses for
 * its list filters — exposed in Core so any list view that wants the same
 * "Images / Videos / PDF / Other" UX (GED Documents, future modules) can plug
 * the same enum into its repository + UI without duplicating the logic.
 *
 * Bind each case to a Doctrine `LIKE`/`=` clause via `applyTo($qb, $alias)`.
 */
enum MimeGroupEnum: string
{
    case Image = 'image';
    case Video = 'video';
    case Pdf = 'pdf';
    case Other = 'other';

    /**
     * Append the matching condition to `$qb` against `<alias>.mimeType`.
     */
    public function applyTo(QueryBuilder $qb, string $alias): void
    {
        match ($this) {
            self::Image => $qb->andWhere(sprintf("%s.mimeType LIKE 'image/%%'", $alias)),
            self::Video => $qb->andWhere(sprintf("%s.mimeType LIKE 'video/%%'", $alias)),
            self::Pdf => $qb->andWhere(sprintf("%s.mimeType = 'application/pdf'", $alias)),
            self::Other => $qb->andWhere(sprintf(
                "%1\$s.mimeType NOT LIKE 'image/%%' AND %1\$s.mimeType NOT LIKE 'video/%%' AND %1\$s.mimeType <> 'application/pdf'",
                $alias,
            )),
        };
    }
}
