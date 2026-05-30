<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Scheduler;

use Aurora\Core\Scheduler\RecurringMessageProviderInterface;
use Aurora\Module\PersonalFinance\Recurring\Message\GeneratePersonalFinanceRecurringTransactionsMessage;
use Symfony\Component\Scheduler\RecurringMessage;

/**
 * PersonalFinance's recurring jobs, contributed to the core 'main' schedule.
 */
final class PersonalFinanceRecurringMessageProvider implements RecurringMessageProviderInterface
{
    public function getRecurringMessages(): iterable
    {
        yield RecurringMessage::cron('0 3 * * *', new GeneratePersonalFinanceRecurringTransactionsMessage());
    }
}
