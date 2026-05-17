<?php

declare(strict_types=1);

namespace Aurora\Core\Module\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

use function Symfony\Component\String\u;

/**
 * Scaffolds a new Aurora module (cas 1 — stateless minimal).
 *
 *   bin/console aurora:make:module Loyalty
 *
 * Generates the minimum-viable wiring : <X>Module.php + Controller + Twig
 * + Vue + translations. Auto-detects core vs client context (composer.json
 * name) and adapts namespaces, asset paths, and post-generation hints.
 *
 * Out of scope (MVP) : Cas 2 (toggles + Context), Cas 3 (CRUD entities),
 * Cas 4 (frontend descriptor), Cas 5 (settings tab). These are documented
 * in {@see /docs/aurora-core/dev/add_module.md} and will be added to this
 * command incrementally.
 *
 * @see \Aurora\Core\Module\Command\Tests\MakeModuleCommandTest (TODO)
 */
#[AsCommand(
    name: 'aurora:make:module',
    description: 'Scaffold a new Aurora module (cas 1 — stateless minimal).',
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

    private string $projectDir;

    private string $templateDir;

    private Filesystem $fs;

    public function __construct(string $projectDir)
    {
        parent::__construct();
        $this->projectDir = rtrim($projectDir, '/');
        $this->templateDir = __DIR__.'/templates';
        $this->fs = new Filesystem();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Module name in PascalCase (e.g. Loyalty, Tracking, WikiNotes).')
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
        if (\in_array($derived['module'], self::RESERVED_NAMES, true)) {
            $io->error(sprintf('"%s" is a reserved name (infra folder).', $derived['module']));

            return Command::FAILURE;
        }

        $moduleDir = sprintf('%s/src/Module/%s', $this->projectDir, $derived['module']);
        if (is_dir($moduleDir)) {
            $io->error(sprintf('Module folder already exists : %s', $moduleDir));

            return Command::FAILURE;
        }

        // 4. Extra inputs
        $label = $io->ask('Display label (free text — used in nav + settings)', $derived['module']);
        $icon = $io->ask('Sidemenu icon (kebab-case Lucide name, e.g. flame, key-round)', 'flame');
        $priority = (int) $io->ask('NavSection priority (lower = higher in sidemenu)', '60');

        $vars = [
            '{{MODULE}}' => $derived['module'],
            '{{MODULE_ID}}' => $derived['snake'],
            '{{MODULE_KEBAB}}' => $derived['kebab'],
            '{{MODULE_LABEL}}' => $label,
            '{{ICON}}' => $icon,
            '{{PRIORITY}}' => (string) $priority,
            '{{NAMESPACE}}' => sprintf('%s\\Module\\%s', $context === 'core' ? 'Aurora' : 'App', $derived['module']),
        ];

        // 5. Confirm
        $io->section('About to generate');
        $io->listing($this->plannedFiles($derived, $context));
        if (!$io->confirm('Proceed ?', true)) {
            return Command::FAILURE;
        }

        // 6. Generate
        $created = [];
        foreach ($this->fileMap($derived, $context) as $template => $target) {
            $rendered = $this->render($template, $vars);
            $this->fs->mkdir(\dirname($target));
            $this->fs->dumpFile($target, $rendered);
            $created[] = str_replace($this->projectDir.'/', '', $target);
        }

        // 7. Wire aliases.js (core only)
        if ($context === 'core') {
            $this->appendAlias($derived);
        }

        // 8. Report
        $io->success(sprintf('Module "%s" scaffolded.', $derived['module']));
        $io->section('Files created');
        $io->listing($created);

        $io->section('Next steps');
        $hints = [
            'make sf CMD="aurora:privileges:sync"  # register the permission',
            'make sf CMD="aurora:menus:sync"        # register the NavItem',
            'make translation                       # dump JSON translations',
            'make cc                                # clear cache',
        ];
        if ($context === 'client') {
            array_unshift(
                $hints,
                'Edit config/packages/twig.yaml — add: '."'%kernel.project_dir%/templates/Module/{$derived['module']}': '{$derived['module']}'",
                'Edit config/services.yaml — add to DumpJsTranslationsCommand $extraSourceDirs:',
                "  - '%kernel.project_dir%/src/Module/{$derived['module']}/translations'",
            );
        }
        $io->listing($hints);

        $io->note('This MVP covers cas 1 (stateless minimal). For toggles + Context, CRUD entities, frontend descriptor or settings tab, see docs/aurora-core/dev/add_module.md.');

        return Command::SUCCESS;
    }

    private function validateName(string $value): string
    {
        $value = trim($value);
        if ('' === $value) {
            throw new \RuntimeException('Module name cannot be empty.');
        }
        if (!preg_match('/^[A-Z][A-Za-z0-9]+$/', $value)) {
            throw new \RuntimeException('Module name must be PascalCase ASCII (e.g. Loyalty, WikiNotes).');
        }

        return $value;
    }

    /** @return array{module: string, snake: string, kebab: string} */
    private function deriveNames(string $name): array
    {
        return [
            'module' => $name,
            'snake' => u($name)->snake()->toString(),
            'kebab' => str_replace('_', '-', u($name)->snake()->toString()),
        ];
    }

    private function detectContext(): string
    {
        $composer = $this->projectDir.'/composer.json';
        if (!is_file($composer)) {
            return 'client';
        }
        $data = json_decode((string) file_get_contents($composer), true);

        return (isset($data['name']) && $data['name'] === 'axelraboit/aurora') ? 'core' : 'client';
    }

    /** @param array{module: string, snake: string, kebab: string} $d */
    private function plannedFiles(array $d, string $context): array
    {
        $assetsBase = $context === 'core' ? 'assets/Module' : 'assets/client/Module';

        return [
            "src/Module/{$d['module']}/{$d['module']}Module.php",
            "src/Module/{$d['module']}/Controller/Backend/{$d['module']}Controller.php",
            "src/Module/{$d['module']}/translations/messages.fr.yaml",
            "src/Module/{$d['module']}/translations/messages.en.yaml",
            "templates/Module/{$d['module']}/backend/index.html.twig",
            "{$assetsBase}/{$d['module']}/backend/{$d['module']}App.vue",
            ...($context === 'core' ? ['aliases.js (edit: add @'.$d['kebab'].')'] : []),
        ];
    }

    /** @return array<string, string> template-name → absolute target path */
    private function fileMap(array $d, string $context): array
    {
        $assetsBase = $context === 'core' ? 'assets/Module' : 'assets/client/Module';

        return [
            'Module.php.tpl' => sprintf('%s/src/Module/%s/%sModule.php', $this->projectDir, $d['module'], $d['module']),
            'Controller.php.tpl' => sprintf('%s/src/Module/%s/Controller/Backend/%sController.php', $this->projectDir, $d['module'], $d['module']),
            'messages.fr.yaml.tpl' => sprintf('%s/src/Module/%s/translations/messages.fr.yaml', $this->projectDir, $d['module']),
            'messages.en.yaml.tpl' => sprintf('%s/src/Module/%s/translations/messages.en.yaml', $this->projectDir, $d['module']),
            'index.html.twig.tpl' => sprintf('%s/templates/Module/%s/backend/index.html.twig', $this->projectDir, $d['module']),
            'App.vue.tpl' => sprintf('%s/%s/%s/backend/%sApp.vue', $this->projectDir, $assetsBase, $d['module'], $d['module']),
        ];
    }

    private function render(string $template, array $vars): string
    {
        $path = $this->templateDir.'/'.$template;
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Template not found : %s', $path));
        }

        return strtr((string) file_get_contents($path), $vars);
    }

    /** @param array{module: string, snake: string, kebab: string} $d */
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
}
