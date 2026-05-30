<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Scheduler;

use Aurora\Core\Scheduler\RecurringMessageProviderInterface;
use Aurora\Module\Billing\Ocr\Message\RecoverStuckOcrJobsMessage;
use Symfony\Component\Scheduler\RecurringMessage;

/**
 * Billing's recurring jobs, contributed to the core 'main' schedule.
 */
final class BillingRecurringMessageProvider implements RecurringMessageProviderInterface
{
    public function getRecurringMessages(): iterable
    {
        yield RecurringMessage::cron('30 * * * *', new RecoverStuckOcrJobsMessage());
    }
}
