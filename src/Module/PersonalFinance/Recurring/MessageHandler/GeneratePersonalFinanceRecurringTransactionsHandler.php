<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\MessageHandler;

use Aurora\Module\PersonalFinance\Recurring\Manager\PersonalFinanceRecurringTransactionManagerInterface;
use Aurora\Module\PersonalFinance\Recurring\Message\GeneratePersonalFinanceRecurringTransactionsMessage;
use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceRecurringTransactionRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Daily Scheduler tick — materialises every PersonalFinanceRecurringTransaction
 * rule whose dayOfMonth has passed and that hasn't been generated yet
 * this month.
 *
 * Idempotent: the underlying generateIfDue() bails out via the
 * lastGeneratedAt Y-m comparison if the rule was already materialised
 * today, so re-firing the same tick (e.g. after a missed run replayed
 * by Scheduler's processOnlyLastMissedRun) cannot duplicate.
 *
 * The {@see GeneratePersonalFinanceRecurringTransactionsCommand}
 * console command remains the back-fill / debug entry point
 * (--dry-run, --date=YYYY-MM-DD), but in production the Scheduler
 * fires this handler automatically.
 */
#[AsMessageHandler]
final readonly class GeneratePersonalFinanceRecurringTransactionsHandler
{
    public function __construct(
        private PersonalFinanceRecurringTransactionRepository $recurringRepository,
        private PersonalFinanceRecurringTransactionManagerInterface $recurringManager,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(GeneratePersonalFinanceRecurringTransactionsMessage $message): void
    {
        $today = new DateTimeImmutable('today');
        $candidates = $this->recurringRepository->findActiveDueOn($today);

        if ([] === $candidates) {
            return;
        }

        $generated = 0;
        foreach ($candidates as $rec) {
            $tx = $this->recurringManager->generateIfDue($rec, $today);
            if (null !== $tx) {
                ++$generated;
            }
        }

        $this->logger->info('PersonalFinance: generated {generated} recurring transactions (out of {candidates} candidate(s)) for {date}.', [
            'generated' => $generated,
            'candidates' => count($candidates),
            'date' => $today->format('Y-m-d'),
        ]);
    }
}
