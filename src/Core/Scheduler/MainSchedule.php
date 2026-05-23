<?php

declare(strict_types=1);

namespace Aurora\Core\Scheduler;

use Aurora\Core\Scheduler\Message\CleanTempFilesMessage;
use Aurora\Module\Billing\Ocr\Message\RecoverStuckOcrJobsMessage;
use Aurora\Module\Editorial\Post\Message\PublishScheduledPostsMessage;
use Aurora\Module\Editorial\Post\Message\PurgeTrashedPostsMessage;
use Aurora\Module\PersonalFinance\Recurring\Message\GeneratePersonalFinanceRecurringTransactionsMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('main')]
final readonly class MainSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function getSchedule(): Schedule
    {
        return new Schedule()
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            ->add(
                RecurringMessage::cron('* * * * *', new PublishScheduledPostsMessage()),
            )
            ->add(
                RecurringMessage::cron('0 3 * * *', new PurgeTrashedPostsMessage()),
            )
            ->add(
                RecurringMessage::cron('0 * * * *', new CleanTempFilesMessage()),
            )
            ->add(
                RecurringMessage::cron('30 * * * *', new RecoverStuckOcrJobsMessage()),
            )
            ->add(
                RecurringMessage::cron('0 3 * * *', new GeneratePersonalFinanceRecurringTransactionsMessage()),
            );
    }
}
