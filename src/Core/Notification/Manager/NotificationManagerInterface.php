<?php

declare(strict_types=1);

namespace Aurora\Core\Notification\Manager;

use Aurora\Core\Notification\Entity\NotificationInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;

interface NotificationManagerInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function notify(
        CoreUserInterface $recipient,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        array $data = [],
    ): NotificationInterface;

    public function markRead(NotificationInterface $notification): void;

    public function markAllReadForUser(User $user): int;

    public function delete(NotificationInterface $notification): void;

    public function deleteAllForUser(User $user): int;
}
