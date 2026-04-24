<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Setting;
use App\Enum\ApplicationParameter\ApplicationParameterEnum;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'velox:application-parameter',
    description: 'Synchronise les paramètres applicatifs (crée les manquants, supprime les obsolètes).',
    aliases: ['velox:ap'],
)]
class ApplicationParameterCommand extends Command
{
    public function __construct(
        private readonly SettingRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les changements sans les appliquer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $symfonyStyle->note('Mode dry-run — aucun changement ne sera enregistré.');
        }

        $enumCases = ApplicationParameterEnum::cases();
        $enumKeys = array_map(fn ($enumCase): string => $enumCase->getKey(), $enumCases);
        $existing = [];

        foreach ($this->repository->findAll() as $param) {
            $existing[$param->getKey()] = $param;
        }

        $created = $this->createMissing($enumCases, $existing, $symfonyStyle, $dryRun);
        $synced = $this->syncMetadata($enumCases, $existing, $symfonyStyle, $dryRun);
        $deleted = $this->deleteObsolete($enumKeys, $existing, $symfonyStyle, $dryRun);

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $symfonyStyle->success(sprintf('%d créé(s), %d mis à jour, %d supprimé(s).', $created, $synced, $deleted));

        return Command::SUCCESS;
    }

    /**
     * @param ApplicationParameterEnum[] $enumCases
     * @param array<string, Setting>          $existing
     */
    private function createMissing(array $enumCases, array $existing, SymfonyStyle $symfonyStyle, bool $dryRun): int
    {
        $created = 0;

        foreach ($enumCases as $case) {
            if (isset($existing[$case->getKey()])) {
                continue;
            }

            $symfonyStyle->writeln(sprintf('  <info>+</info> %s (défaut : %s)', $case->getKey(), $case->getDefaultValue()));
            ++$created;

            if (!$dryRun) {
                $this->entityManager->persist(new Setting(
                    key: $case->getKey(),
                    value: $case->getDefaultValue(),
                    description: $case->getDescription(),
                    type: $case->getType(),
                    group: $case->getGroup(),
                ));
            }
        }

        return $created;
    }

    /**
     * @param ApplicationParameterEnum[] $enumCases
     * @param array<string, Setting>          $existing
     */
    private function syncMetadata(array $enumCases, array $existing, SymfonyStyle $symfonyStyle, bool $dryRun): int
    {
        $synced = 0;

        foreach ($enumCases as $case) {
            $param = $existing[$case->getKey()] ?? null;
            if (!$param instanceof Setting) {
                continue;
            }

            $changed = false;
            if ($param->getDescription() !== $case->getDescription()) {
                $changed = true;
                if (!$dryRun) {
                    $param->setDescription($case->getDescription());
                }
            }

            if ($param->getType() !== $case->getType()) {
                $changed = true;
                if (!$dryRun) {
                    $param->setType($case->getType());
                }
            }

            if ($param->getGroup() !== $case->getGroup()) {
                $changed = true;
                if (!$dryRun) {
                    $param->setGroup($case->getGroup());
                }
            }

            if ($changed) {
                $symfonyStyle->writeln(sprintf('  <comment>~</comment> %s (métadonnées mises à jour)', $case->getKey()));
                ++$synced;
            }
        }

        return $synced;
    }

    /**
     * @param string[]               $enumKeys
     * @param array<string, Setting> $existing
     */
    private function deleteObsolete(array $enumKeys, array $existing, SymfonyStyle $symfonyStyle, bool $dryRun): int
    {
        $deleted = 0;

        foreach ($existing as $key => $param) {
            if (in_array($key, $enumKeys, true)) {
                continue;
            }

            $symfonyStyle->writeln(sprintf('  <fg=red>-</fg=red> %s (obsolète)', $key));
            ++$deleted;

            if (!$dryRun) {
                $this->entityManager->remove($param);
            }
        }

        return $deleted;
    }
}
