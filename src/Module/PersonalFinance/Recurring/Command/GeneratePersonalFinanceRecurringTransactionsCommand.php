<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Command;

use Aurora\Module\PersonalFinance\Recurring\Manager\PersonalFinanceRecurringTransactionManagerInterface;
use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceRecurringTransactionRepository;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'personal-finance:recurring:generate',
    description: 'Materialise the due PersonalFinanceRecurringTransaction rules into real transactions for the given date (defaults to today).',
)]
final class GeneratePersonalFinanceRecurringTransactionsCommand extends Command
{
    public function __construct(
        private readonly PersonalFinanceRecurringTransactionRepository $recurringRepository,
        private readonly PersonalFinanceRecurringTransactionManagerInterface $recurringManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('date', null, InputOption::VALUE_REQUIRED, 'YYYY-MM-DD reference date (defaults to today). Useful for back-filling a missed cron pass.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be generated without persisting.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $today = $this->resolveDate($input->getOption('date'));
        $dryRun = (bool) $input->getOption('dry-run');

        $candidates = $this->recurringRepository->findActiveDueOn($today);
        $io->title(sprintf('PersonalFinance — generating recurring transactions for %s', $today->format('Y-m-d')));
        $io->note(sprintf('%d candidate(s).', count($candidates)));

        $generated = 0;
        $skipped = 0;

        foreach ($candidates as $rec) {
            $label = sprintf('#%d %s — wallet=%d day=%d amount=%s', $rec->getId(), (string) $rec->getDescription(), (int) $rec->getWallet()->getId(), $rec->getDayOfMonth(), $rec->getAmount());

            if ($dryRun) {
                $io->writeln('  [DRY] '.$label);
                ++$generated;
                continue;
            }

            $tx = $this->recurringManager->generateIfDue($rec, $today);
            if (null === $tx) {
                $io->writeln('  [skip] '.$label);
                ++$skipped;
                continue;
            }

            $io->writeln(sprintf('  [ok]   %s -> tx #%d', $label, (int) $tx->getId()));
            ++$generated;
        }

        $io->success(sprintf('Generated: %d, Skipped: %d.', $generated, $skipped));

        return Command::SUCCESS;
    }

    private function resolveDate(?string $value): DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return new DateTimeImmutable('today');
        }
        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return new DateTimeImmutable('today');
        }
    }
}
