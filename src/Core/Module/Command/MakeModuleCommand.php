<?php

declare(strict_types=1);

namespace Aurora\Core\Module\Command;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;
use function in_array;
use function Symfony\Component\String\u;

/**
 * Scaffolds a new Aurora module covering the 5 cas types of the
 * convention documented in {@see /docs/aurora-core/dev/add_module.md}.
 *
 *   bin/console aurora:make:module Loyalty
 *   bin/console aurora:make:module Loyalty --with-toggles --with-settings
 *   bin/console aurora:make:module Tracking --with-toggles --with-frontend --with-settings
 *
 * Cas covered :
 *   - Cas 1 — stateless minimal (always)
 *   - Cas 2 — toggles + Context        : --with-toggles
 *   - Cas 3 — CRUD entity              : NOT generated here, defer to
 *                                         `/add-entity` skill or
 *                                         `bin/console make:entity`
 *   - Cas 4 — public frontend          : --with-frontend
 *   - Cas 5 — settings tab provider    : --with-settings
 *
 * Auto-detects core vs client context (composer.json name).
 *
 * @see MakeModuleCommandTest (TODO)
 */
#[AsCommand(
    name: 'aurora:make:module',
    description: 'Scaffold a new Aurora module (5 cas types — flags select the extras).',
)]
final class MakeModuleCommand extends Command
{
    /** Reserved module names. Refuse to scaffold these (already exist or conflict with infra). */
    private const array RESERVED_NAMES = [
        'Module',     // src/Core/Module/ — infra
        'Mail', 'Locale', 'Encryption', 'Notification', 'Storage', 'Scheduler',
        'Sequence', 'Support', 'Frontend', 'Twig', 'Validation', 'Timestampable',
        'Repository', 'EventSubscriber', 'Migration', 'DataFixtures', 'Enum',
    ];

    private readonly string $projectDir;

    private readonly string $templateDir;

    private readonly Filesystem $fs;

    public function __construct(string $projectDir)
    {
        parent::__construct();
        $this->projectDir = mb_rtrim($projectDir, '/');
        $this->templateDir = __DIR__.'/templates';
        $this->fs = new Filesystem();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Module name in PascalCase (e.g. Loyalty, Tracking, WikiNotes).')
            ->addOption('with-toggles', null, InputOption::VALUE_NONE, 'Cas 2 — add <X>Context + ModuleToggleProviderInterface (togglable backend).')
            ->addOption('with-frontend', null, InputOption::VALUE_NONE, 'Cas 4 — add <X>FrontendDescriptor (public-facing module).')
            ->addOption('with-settings', null, InputOption::VALUE_NONE, 'Cas 5 — add Setting/<X>SettingEnum + ConfigurationTabProvider.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Module name
        $name = $input->getArgument('name');
        if (null === $name || '' === $name) {
            $name = $io->ask('Module name (PascalCase)', null, $this->validateName(...));
        } else {
            $this->validateName($name);
        }

        $derived = $this->deriveNames($name);

        // 2. Context detection
        $context = $this->detectContext();
        $io->section(sprintf('Detected context : %s', $context));

        // 3. Reserved checks
        if (in_array($derived['module'], self::RESERVED_NAMES, true)) {
            $io->error(sprintf('"%s" is a reserved name (infra folder).', $derived['module']));

            return Command::FAILURE;
        }

        $moduleDir = sprintf('%s/src/Module/%s', $this->projectDir, $derived['module']);
        if (is_dir($moduleDir)) {
            $io->error(sprintf('Module folder already exists : %s', $moduleDir));

            return Command::FAILURE;
        }

        // 4. Cas selection (flags or interactive)
        $withToggles = (bool) $input->getOption('with-toggles');
        $withFrontend = (bool) $input->getOption('with-frontend');
        $withSettings = (bool) $input->getOption('with-settings');

        if (!$withToggles && !$withFrontend && !$withSettings && $input->isInteractive()) {
            $io->section('Optional cas types');
            $withToggles = $io->confirm('Add toggles + Context (cas 2) ?', false);
            $withFrontend = $io->confirm('Add FrontendDescriptor for a public site (cas 4) ?', false);
            $withSettings = $io->confirm('Add Settings tab provider (cas 5) ?', false);
        }

        // 5. Extra inputs
        $label = $io->ask('Display label (free text — used in nav + settings)', $derived['module']);
        $icon = $io->ask('Sidemenu icon (kebab-case Lucide name, e.g. flame, key-round)', 'flame');
        $priority = (int) $io->ask('NavSection priority (lower = higher in sidemenu)', '60');

        $vars = [
            '{{MODULE}}' => $derived['module'],
            '{{MODULE_ID}}' => $derived['snake'],
            '{{MODULE_KEBAB}}' => $derived['kebab'],
            '{{MODULE_VAR}}' => $derived['camel'],
            '{{MODULE_LABEL}}' => $label,
            '{{ICON}}' => $icon,
            '{{PRIORITY}}' => (string) $priority,
            '{{NAMESPACE}}' => sprintf('%s\\Module\\%s', 'core' === $context ? 'Aurora' : 'App', $derived['module']),
        ];

        // 6. Confirm
        $fileMap = $this->fileMap($derived, $context, $withToggles, $withFrontend, $withSettings);
        $io->section('About to generate');
        $io->listing(array_map(fn (string $p): string => str_replace($this->projectDir.'/', '', $p), array_values($fileMap)));
        if ('core' === $context) {
            $io->writeln('Plus edit: aliases.js (append @'.$derived['kebab'].')');
        }

        if (!$io->confirm('Proceed ?', true)) {
            return Command::FAILURE;
        }

        // 7. Generate
        $created = [];
        foreach ($fileMap as $template => $target) {
            $rendered = $this->render($template, $vars);
            $this->fs->mkdir(dirname($target));
            $this->fs->dumpFile($target, $rendered);
            $created[] = str_replace($this->projectDir.'/', '', $target);
        }

        // 8. Wire aliases.js (core only)
        if ('core' === $context) {
            $this->appendAlias($derived);
            $created[] = 'aliases.js (edited)';
        }

        // 9. Report
        $io->success(sprintf('Module "%s" scaffolded.', $derived['module']));
        $io->section('Files created');
        $io->listing($created);

        $this->printNextSteps($io, $derived, $context, $withToggles, $withFrontend, $withSettings);

        return Command::SUCCESS;
    }

    private function validateName(string $value): string
    {
        $value = mb_trim($value);
        if ('' === $value) {
            throw new RuntimeException('Module name cannot be empty.');
        }

        if (!preg_match('/^[A-Z][A-Za-z0-9]+$/', $value)) {
            throw new RuntimeException('Module name must be PascalCase ASCII (e.g. Loyalty, WikiNotes).');
        }

        return $value;
    }

    /** @return array{module: string, snake: string, kebab: string, camel: string} */
    private function deriveNames(string $name): array
    {
        return [
            'module' => $name,
            'snake' => u($name)->snake()->toString(),
            'kebab' => str_replace('_', '-', u($name)->snake()->toString()),
            'camel' => u($name)->camel()->toString(),
        ];
    }

    private function detectContext(): string
    {
        $composer = $this->projectDir.'/composer.json';
        if (!is_file($composer)) {
            return 'client';
        }

        $data = json_decode((string) file_get_contents($composer), true);

        return (isset($data['name']) && 'axelraboit/aurora' === $data['name']) ? 'core' : 'client';
    }

    /**
     * @param array{module: string, snake: string, kebab: string, camel: string} $d
     *
     * @return array<string, string> template-name → absolute target path
     */
    private function fileMap(array $d, string $context, bool $withToggles, bool $withFrontend, bool $withSettings): array
    {
        // Core modules co-locate their Vue assets under src/Module/<X>/assets/.
        // Client modules still use the assets/client/Module/<X>/ layout (the
        // client project keeps a dedicated assets/ root for its own code).
        $appVuePath = 'core' === $context
            ? sprintf('%s/src/Module/%s/assets/backend/%sApp.vue', $this->projectDir, $d['module'], $d['module'])
            : sprintf('%s/assets/client/Module/%s/backend/%sApp.vue', $this->projectDir, $d['module'], $d['module']);

        // Always — cas 1
        $moduleTemplate = $withToggles
            ? sprintf('Module.WithToggles.%s.php.tpl', $context)
            : 'Module.php.tpl';

        $map = [
            $moduleTemplate => sprintf('%s/src/Module/%s/%sModule.php', $this->projectDir, $d['module'], $d['module']),
            'Controller.php.tpl' => sprintf('%s/src/Module/%s/Controller/Backend/%sController.php', $this->projectDir, $d['module'], $d['module']),
            'messages.fr.yaml.tpl' => sprintf('%s/src/Module/%s/translations/messages.fr.yaml', $this->projectDir, $d['module']),
            'messages.en.yaml.tpl' => sprintf('%s/src/Module/%s/translations/messages.en.yaml', $this->projectDir, $d['module']),
            'index.html.twig.tpl' => sprintf('%s/src/Module/%s/templates/backend/index.html.twig', $this->projectDir, $d['module']),
            'App.vue.tpl' => $appVuePath,
        ];

        // Cas 2
        if ($withToggles) {
            $contextTemplate = sprintf('Context.%s.php.tpl', $context);
            $map[$contextTemplate] = sprintf('%s/src/Module/%s/%sContext.php', $this->projectDir, $d['module'], $d['module']);
        }

        // Cas 4
        if ($withFrontend) {
            $frontendTemplate = sprintf('FrontendDescriptor.%s.php.tpl', $context);
            $map[$frontendTemplate] = sprintf('%s/src/Module/%s/%sFrontendDescriptor.php', $this->projectDir, $d['module'], $d['module']);
        }

        // Cas 5
        if ($withSettings) {
            $map['SettingEnum.php.tpl'] = sprintf('%s/src/Module/%s/Setting/%sSettingEnum.php', $this->projectDir, $d['module'], $d['module']);
            $map['ConfigurationTabProvider.php.tpl'] = sprintf('%s/src/Module/%s/Setting/%sConfigurationTabProvider.php', $this->projectDir, $d['module'], $d['module']);
        }

        return $map;
    }

    private function render(string $template, array $vars): string
    {
        $path = $this->templateDir.'/'.$template;
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Template not found : %s', $path));
        }

        return strtr((string) file_get_contents($path), $vars);
    }

    /** @param array{module: string, snake: string, kebab: string, camel: string} $d */
    private function appendAlias(array $d): void
    {
        $aliasesPath = $this->projectDir.'/aliases.js';
        if (!is_file($aliasesPath)) {
            return;
        }

        $content = (string) file_get_contents($aliasesPath);
        $newAlias = sprintf('    "@%s": moduleAlias("%s"),', $d['kebab'], $d['module']);

        // Skip if alias already present
        if (str_contains($content, sprintf('"@%s"', $d['kebab']))) {
            return;
        }

        // Insert before the closing `};`
        $patched = preg_replace('/^(\};\s*)$/m', $newAlias."\n".'$1', $content, 1);
        if (null !== $patched && $patched !== $content) {
            $this->fs->dumpFile($aliasesPath, $patched);
        }
    }

    /** @param array{module: string, snake: string, kebab: string, camel: string} $d */
    private function printNextSteps(SymfonyStyle $io, array $d, string $context, bool $withToggles, bool $withFrontend, bool $withSettings): void
    {
        $io->section('Next steps');

        $hints = [];

        // Client-only manual wiring
        if ('client' === $context) {
            $hints[] = sprintf("Edit config/packages/twig.yaml — add: '%%kernel.project_dir%%/src/Module/%s/templates': '%s'", $d['module'], $d['module']);
            $hints[] = 'Edit config/services.yaml — add to DumpJsTranslationsCommand $extraSourceDirs:';
            $hints[] = sprintf("  - '%%kernel.project_dir%%/src/Module/%s/translations'", $d['module']);
        }

        // Cas 2 — toggles
        if ($withToggles && 'core' === $context) {
            $hints[] = sprintf(
                'Edit src/Module/Configuration/Setting/Enum/ModuleParameterEnum.php — add: case %sBackend = \'modules_%s_backend\';',
                $d['module'],
                $d['snake']
            );
            $hints[] = '  ⚠ ModuleParameterEnum has match() expressions — also add an arm in each match (getLabel, getDescription, getDefaultValue, getType, getGroup) for the new case, else PHPStan will complain.';
            $hints[] = 'Then run: make sf CMD="aurora:application-parameter"  # sync the new enum case into core_settings';
        }

        // Cas 4 — frontend
        if ($withFrontend && 'core' === $context) {
            $hints[] = sprintf(
                'Edit src/Module/Configuration/Setting/Enum/ModuleParameterEnum.php — add: case %sFrontend = \'modules_%s_frontend\'; (same match-arm completion needed)',
                $d['module'],
                $d['snake']
            );
        }

        if ($withFrontend && 'client' === $context && !$withToggles) {
            $hints[] = sprintf(
                'FrontendDescriptor references %sContext::FRONTEND_KEY — you need to ADD a Context first (re-run with --with-toggles or write it manually).',
                $d['module']
            );
        }

        if ($withFrontend && 'client' === $context && $withToggles) {
            $hints[] = sprintf(
                'Edit src/Module/%s/%sContext.php — add: public const string FRONTEND_KEY = \'app_%s_frontend\';',
                $d['module'],
                $d['module'],
                $d['snake']
            );
        }

        // Standard post-gen
        $hints[] = 'make sf CMD="aurora:privileges:sync"  # register the permission';
        $hints[] = 'make sf CMD="aurora:menus:sync"        # register the NavItem';
        $hints[] = 'make translation                       # dump JSON translations';
        $hints[] = 'make cc                                # clear cache';

        // Cas 3 hint
        $hints[] = '';
        $hints[] = 'For a CRUD entity inside this module, use:';
        $hints[] = '  • Claude Code skill: /add-entity';
        $hints[] = '  • Or Symfony native:  bin/console make:entity';

        $io->listing($hints);

        // Summary of what was wired
        $cases = ['1 (stateless minimal)'];
        if ($withToggles) {
            $cases[] = '2 (toggles + Context)';
        }

        if ($withFrontend) {
            $cases[] = '4 (FrontendDescriptor)';
        }

        if ($withSettings) {
            $cases[] = '5 (Settings tab provider)';
        }

        $io->note(sprintf('Generated cas: %s. Cas 3 (CRUD entity) → see hints above.', implode(', ', $cases)));
    }
}
