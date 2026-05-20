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
 * Scaffolds a new Aurora module following the convention documented in
 * {@see /docs/aurora-core/dev/add_module.md}.
 *
 *   bin/console aurora:make:module Loyalty
 *   bin/console aurora:make:module Loyalty --with-settings
 *   bin/console aurora:make:module Tracking --with-frontend --with-settings
 *   bin/console aurora:make:module DevTools --no-toggle           # infra-only
 *
 * Defaults & opt-outs :
 *   - Module is **toggleable by default** — gets a `<X>Context` with
 *     `isBackendEnabled()` + implements `ModuleToggleProviderInterface`.
 *     This matches the convention: every module appears in the admin
 *     "Modules access" panel with an ON/OFF switch. Pass `--no-toggle`
 *     for infra-only modules that must always be on (Dev-style).
 *   - CRUD entity (cas 3 in the convention doc) is NOT generated here —
 *     defer to `/add-entity` skill or `bin/console make:entity`.
 *   - Public frontend (cas 4) : opt-in via `--with-frontend`.
 *   - Settings tab (cas 5) : opt-in via `--with-settings`.
 *
 * Sub-features (e.g. Vault = Safe + PasswordGenerator) — beyond the basic
 * backend toggle — are added separately via the `/add-submodule` skill,
 * not by this command.
 *
 * Auto-detects core vs client context (composer.json name).
 *
 * @see MakeModuleCommandTest (TODO)
 */
#[AsCommand(
    name: 'aurora:make:module',
    description: 'Scaffold a new Aurora module (togglable backend by default; --no-toggle / --with-frontend / --with-settings adjust the layers).',
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
            ->addOption('no-toggle', null, InputOption::VALUE_NONE, 'Opt out of the default backend toggle (skip <X>Context + ModuleToggleProviderInterface). Use only for infra-only modules that must always be on (Dev-style).')
            ->addOption('with-frontend', null, InputOption::VALUE_NONE, 'Add <X>FrontendDescriptor (public-facing module).')
            ->addOption('with-settings', null, InputOption::VALUE_NONE, 'Add Setting/<X>SettingEnum + ConfigurationTabProvider (own tab in /backend/settings).')
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

        // 4. Optional layers (flags or interactive). Backend toggle is ON by
        // default; opt out via --no-toggle for infra-only modules.
        $withToggle = !(bool) $input->getOption('no-toggle');
        $withFrontend = (bool) $input->getOption('with-frontend');
        $withSettings = (bool) $input->getOption('with-settings');

        if (!$withFrontend && !$withSettings && $input->isInteractive()) {
            $io->section('Optional layers');
            $withFrontend = $io->confirm(
                'Module exposes public-facing pages (visitors hit a /something URL, not just /backend)?',
                false,
            );
            $withSettings = $io->confirm(
                'Module contributes its own tab in /backend/settings (config values, API keys, feature flags edited by admins)?',
                false,
            );
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
        $fileMap = $this->fileMap($derived, $context, $withToggle, $withFrontend, $withSettings);
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

        $this->printNextSteps($io, $derived, $context, $withToggle, $withFrontend, $withSettings);

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
    private function fileMap(array $d, string $context, bool $withToggle, bool $withFrontend, bool $withSettings): array
    {
        // Core modules co-locate their Vue assets under src/Module/<X>/assets/.
        // Client modules still use the assets/client/Module/<X>/ layout (the
        // client project keeps a dedicated assets/ root for its own code).
        $appVuePath = 'core' === $context
            ? sprintf('%s/src/Module/%s/assets/backend/%sApp.vue', $this->projectDir, $d['module'], $d['module'])
            : sprintf('%s/assets/client/Module/%s/backend/%sApp.vue', $this->projectDir, $d['module'], $d['module']);

        // Module class: togglable variant is the default. Opt out via
        // `--no-toggle` for infra-only modules (Dev-style) — uses the
        // bare Module.NoToggle.php.tpl which has no Context dependency.
        $moduleTemplate = $withToggle
            ? sprintf('Module.%s.php.tpl', $context)
            : 'Module.NoToggle.php.tpl';

        $map = [
            $moduleTemplate => sprintf('%s/src/Module/%s/%sModule.php', $this->projectDir, $d['module'], $d['module']),
            'Controller.php.tpl' => sprintf('%s/src/Module/%s/Controller/Backend/%sController.php', $this->projectDir, $d['module'], $d['module']),
            'messages.fr.yaml.tpl' => sprintf('%s/src/Module/%s/translations/messages.fr.yaml', $this->projectDir, $d['module']),
            'messages.en.yaml.tpl' => sprintf('%s/src/Module/%s/translations/messages.en.yaml', $this->projectDir, $d['module']),
            'index.html.twig.tpl' => sprintf('%s/src/Module/%s/templates/backend/index.html.twig', $this->projectDir, $d['module']),
            'App.vue.tpl' => $appVuePath,
        ];

        // <Module>Context — generated by default alongside the togglable Module class.
        if ($withToggle) {
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
    private function printNextSteps(SymfonyStyle $io, array $d, string $context, bool $withToggle, bool $withFrontend, bool $withSettings): void
    {
        $io->section('Next steps');

        $hints = [];

        // Client-only manual wiring
        if ('client' === $context) {
            $hints[] = sprintf("Edit config/packages/twig.yaml — add: '%%kernel.project_dir%%/src/Module/%s/templates': '%s'", $d['module'], $d['module']);
            $hints[] = 'Edit config/services.yaml — add to DumpJsTranslationsCommand $extraSourceDirs:';
            $hints[] = sprintf("  - '%%kernel.project_dir%%/src/Module/%s/translations'", $d['module']);
        }

        // Backend toggle wiring (core) — always printed unless --no-toggle.
        if ($withToggle && 'core' === $context) {
            $hints[] = sprintf(
                'Edit src/Module/Configuration/Setting/Enum/ModuleParameterEnum.php — add: case %sBackend = \'modules_%s_backend\';',
                $d['module'],
                $d['snake']
            );
            $hints[] = '  ⚠ ModuleParameterEnum has match() expressions — also add an arm in each match (getLabel, getDescription, getDefaultValue, getType, getGroup) for the new case, else PHPStan will complain.';
            $hints[] = 'Then run: make sf CMD="aurora:application-parameter"  # sync the new enum case into core_settings';
        }

        // Public frontend wiring (core)
        if ($withFrontend && 'core' === $context) {
            $hints[] = sprintf(
                'Edit src/Module/Configuration/Setting/Enum/ModuleParameterEnum.php — add: case %sFrontend = \'modules_%s_frontend\'; (same match-arm completion needed)',
                $d['module'],
                $d['snake']
            );
        }

        if ($withFrontend && 'client' === $context && !$withToggle) {
            $hints[] = sprintf(
                'FrontendDescriptor references %sContext::FRONTEND_KEY — you need a Context first (re-run without --no-toggle, or write it manually).',
                $d['module']
            );
        }

        if ($withFrontend && 'client' === $context && $withToggle) {
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

        // CRUD entity hint
        $hints[] = '';
        $hints[] = 'For a CRUD entity inside this module, use:';
        $hints[] = '  • Claude Code skill: /add-entity';
        $hints[] = '  • Or Symfony native:  bin/console make:entity';

        $hints[] = '';
        $hints[] = 'For an additional togglable sub-feature (e.g. Vault = Safe + PasswordGenerator),';
        $hints[] = 'use:  Claude Code skill: /add-submodule';

        $io->listing($hints);

        // Summary of what was wired
        $layers = $withToggle ? ['Module + Context (togglable backend)'] : ['Module (always-on, --no-toggle)'];
        if ($withFrontend) {
            $layers[] = 'FrontendDescriptor (public-facing)';
        }

        if ($withSettings) {
            $layers[] = 'Settings tab provider';
        }

        $io->note(sprintf('Generated layers: %s. CRUD entity → /add-entity. Sub-features → /add-submodule.', implode(' + ', $layers)));
    }
}
