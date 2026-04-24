<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Message\PublishScheduledPostsMessage;
use App\Message\PurgeTrashedPostsMessage;
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
            );
    }
}
