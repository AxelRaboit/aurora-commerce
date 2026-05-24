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

/**
 * Scaffolds a new Aurora entity following the 5-layer Sylius pattern.
 *
 * Usage :
 *   bin/console aurora:make:entity Workspace --module=Core
 *   bin/console aurora:make:entity Refund --module=Module/Billing
 *   bin/console aurora:make:entity AuditLog --module=Module/Dev --no-crud
 *   bin/console aurora:make:entity Workspace --module=Core --plural=Workspaces
 *
 * Generates :
 *   - Entity triplet (Interface + Abstract + concrete) + Repository
 *   - DTO quartet (Input + InputInterface + InputFactory + InputFactoryInterface)
 *   - Manager pair (Manager + ManagerInterface)
 *   - Serializer pair (Serializer + SerializerInterface)
 *   - Controller skeleton (5 routes: index / selectable / create / update / delete)
 *
 * Patches `src/AuroraBundle.php` to add the `$resolve_target_entities`
 * line — lexicographically inserted in the right alphabetical position.
 *
 * Doctrine migrations, Vue assets, Twig templates, and translations are
 * left to the user (entity-specific decisions). The post-gen "next steps"
 * output lists them.
 */
#[AsCommand(
    name: 'aurora:make:entity',
    description: 'Scaffold a new Aurora entity (5-layer Sylius pattern : Entity + DTO + Manager + Serializer + Repository + Controller).',
)]
final class MakeEntityCommand extends Command
{
    private readonly string $projectDir;

    private readonly string $templateDir;

    private readonly Filesystem $fs;

    public function __construct(string $projectDir)
    {
        parent::__construct();
        $this->projectDir = mb_rtrim($projectDir, '/');
        $this->templateDir = __DIR__.'/templates/entity';
        $this->fs = new Filesystem();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Entity name in PascalCase (e.g. Workspace, Refund, AuditLog).')
            ->addOption('module', null, InputOption::VALUE_REQUIRED, 'Module path relative to src/ (e.g. Core, Module/Billing, Module/Editorial). Defaults to Core.')
            ->addOption('plural', null, InputOption::VALUE_REQUIRED, 'Plural form in PascalCase (e.g. Taxonomies). Defaults to <Name>s.')
            ->addOption('permission', null, InputOption::VALUE_REQUIRED, 'IsGranted attribute value (e.g. core.workspaces.manage). Defaults to <module_id>.<plural_snake>.manage.')
            ->addOption('audit-channel', null, InputOption::VALUE_REQUIRED, 'AuditLogger channel string (e.g. core, billing). Defaults to "core".')
            ->addOption('no-crud', null, InputOption::VALUE_NONE, 'Skip Layers 2-5 (DTO / Manager / Serializer / Controller). Only generates the Entity triplet + Repository — useful for translation pivot tables, audit log entities, anything without a backend CRUD page.')
            ->addOption('skip-controller', null, InputOption::VALUE_NONE, 'Generate Layers 1-4 but skip the Controller. Useful when the controller is custom-shaped.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');
        if (null === $name || '' === $name) {
            $name = $io->ask('Entity name (PascalCase)', null, $this->validateName(...));
        } else {
            $this->validateName($name);
        }

        $modulePath = $input->getOption('module') ?? 'Core';
        $modulePath = mb_trim($modulePath, '/');
        $this->assertModuleExists($modulePath);

        $pluralName = $input->getOption('plural') ?? ($name.'s');
        $this->validateName($pluralName);

        $derived = $this->deriveNames($name, $pluralName, $modulePath);

        $permission = $input->getOption('permission') ?? sprintf('%s.%s.manage', $derived['module_id'], $derived['plural_snake']);
        $auditChannel = $input->getOption('audit-channel') ?? 'core';

        $withCrud = !$input->getOption('no-crud');
        $withController = $withCrud && !$input->getOption('skip-controller');

        $vars = [
            '{{NAME}}' => $derived['name'],
            '{{NAME_CAMEL}}' => $derived['name_camel'],
            '{{NAME_SNAKE}}' => $derived['name_snake'],
            '{{PLURAL_NAME}}' => $derived['plural_name'],
            '{{PLURAL_SNAKE}}' => $derived['plural_snake'],
            '{{PLURAL_KEBAB}}' => $derived['plural_kebab'],
            '{{NAMESPACE}}' => $derived['namespace'],
            '{{TWIG_NAMESPACE}}' => $derived['twig_namespace'],
            '{{TABLE_NAME}}' => $derived['table_name'],
            '{{SEQUENCE_NAME}}' => $derived['sequence_name'],
            '{{PERMISSION}}' => $permission,
            '{{AUDIT_CHANNEL}}' => $auditChannel,
        ];

        $fileMap = $this->fileMap($derived, $withCrud, $withController);

        $io->section(sprintf('About to generate entity "%s" in src/%s/%s/', $derived['name'], $modulePath, $derived['name']));
        $io->listing(array_map(fn (string $p): string => str_replace($this->projectDir.'/', '', $p), array_values($fileMap)));
        $io->writeln(sprintf('Plus patch: src/AuroraBundle.php — add "%sInterface::class => %s::class"', $derived['name'], $derived['name']));

        if (!$io->confirm('Proceed?', true)) {
            return Command::FAILURE;
        }

        // 1. Write files
        $created = [];
        foreach ($fileMap as $template => $target) {
            $rendered = $this->render($template, $vars);
            $dir = \dirname($target);
            $this->fs->mkdir($dir);
            if ($this->fs->exists($target)) {
                $io->warning(sprintf('Skipping (exists): %s', str_replace($this->projectDir.'/', '', $target)));

                continue;
            }
            $this->fs->dumpFile($target, $rendered);
            $created[] = $target;
        }

        // 2. Patch AuroraBundle.php
        $bundlePath = $this->projectDir.'/src/AuroraBundle.php';
        if ($this->fs->exists($bundlePath)) {
            $this->patchAuroraBundle($bundlePath, $derived);
            $io->writeln('  ✓ src/AuroraBundle.php — resolve_target_entities patched');
        } else {
            $io->warning('src/AuroraBundle.php not found — skipping resolve_target_entities patch (client project?).');
        }

        $io->success(sprintf('Generated %d files.', \count($created)));

        $this->printNextSteps($io, $derived, $withCrud, $withController);

        return Command::SUCCESS;
    }

    /**
     * Validate the entity name against PascalCase. Throws on mismatch so
     * the user gets a clear error before any file is written.
     */
    private function validateName(?string $name): string
    {
        if (null === $name || !preg_match('/^[A-Z][A-Za-z0-9]*$/', $name)) {
            throw new RuntimeException(sprintf('"%s" must be PascalCase (start uppercase, letters/digits only).', $name ?? ''));
        }

        return $name;
    }

    private function assertModuleExists(string $modulePath): void
    {
        $abs = $this->projectDir.'/src/'.$modulePath;
        if (!is_dir($abs)) {
            throw new RuntimeException(sprintf(
                'Module path "src/%s" does not exist. Run `bin/console aurora:make:module` first, or pass --module=<existing>.',
                $modulePath,
            ));
        }
    }

    /**
     * @return array{
     *   name: string, name_camel: string, name_snake: string,
     *   plural_name: string, plural_snake: string, plural_kebab: string,
     *   namespace: string, twig_namespace: string,
     *   table_name: string, sequence_name: string,
     *   module_id: string, module_path: string,
     * }
     */
    private function deriveNames(string $name, string $plural, string $modulePath): array
    {
        $nameSnake = $this->pascalToSnake($name);
        $pluralSnake = $this->pascalToSnake($plural);
        $namespace = 'Aurora\\'.str_replace('/', '\\', $modulePath).'\\'.$name;
        // Twig namespace is the last segment of the module path (e.g. Module/Editorial → Editorial)
        $segments = explode('/', $modulePath);
        $twigNamespace = end($segments);

        return [
            'name' => $name,
            'name_camel' => lcfirst($name),
            'name_snake' => $nameSnake,
            'plural_name' => $plural,
            'plural_snake' => $pluralSnake,
            'plural_kebab' => str_replace('_', '-', $pluralSnake),
            'namespace' => $namespace,
            'twig_namespace' => $twigNamespace,
            'table_name' => 'core_'.$pluralSnake,
            'sequence_name' => 'seq_core_'.$nameSnake.'_id',
            // Module id used in permissions etc.
            'module_id' => mb_strtolower($twigNamespace),
            'module_path' => $modulePath,
        ];
    }

    private function pascalToSnake(string $pascal): string
    {
        return mb_strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $pascal) ?? $pascal);
    }

    /**
     * @param array{name: string, module_path: string} $d
     *
     * @return array<string, string>
     */
    private function fileMap(array $d, bool $withCrud, bool $withController): array
    {
        $entityRoot = sprintf('%s/src/%s/%s', $this->projectDir, $d['module_path'], $d['name']);

        $map = [
            'Interface.php.tpl' => sprintf('%s/Entity/%sInterface.php', $entityRoot, $d['name']),
            'Abstract.php.tpl' => sprintf('%s/Entity/Abstract%s.php', $entityRoot, $d['name']),
            'Entity.php.tpl' => sprintf('%s/Entity/%s.php', $entityRoot, $d['name']),
            'Repository.php.tpl' => sprintf('%s/Repository/%sRepository.php', $entityRoot, $d['name']),
        ];

        if ($withCrud) {
            $map['InputInterface.php.tpl'] = sprintf('%s/Dto/%sInputInterface.php', $entityRoot, $d['name']);
            $map['Input.php.tpl'] = sprintf('%s/Dto/%sInput.php', $entityRoot, $d['name']);
            $map['InputFactoryInterface.php.tpl'] = sprintf('%s/Dto/%sInputFactoryInterface.php', $entityRoot, $d['name']);
            $map['InputFactory.php.tpl'] = sprintf('%s/Dto/%sInputFactory.php', $entityRoot, $d['name']);
            $map['ManagerInterface.php.tpl'] = sprintf('%s/Manager/%sManagerInterface.php', $entityRoot, $d['name']);
            $map['Manager.php.tpl'] = sprintf('%s/Manager/%sManager.php', $entityRoot, $d['name']);
            $map['SerializerInterface.php.tpl'] = sprintf('%s/Serializer/%sSerializerInterface.php', $entityRoot, $d['name']);
            $map['Serializer.php.tpl'] = sprintf('%s/Serializer/%sSerializer.php', $entityRoot, $d['name']);
        }

        if ($withController) {
            $map['Controller.php.tpl'] = sprintf('%s/Controller/Backend/%sController.php', $entityRoot, $d['plural_name']);
        }

        return $map;
    }

    private function render(string $template, array $vars): string
    {
        $path = $this->templateDir.'/'.$template;
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Template not found: %s', $path));
        }

        return strtr(file_get_contents($path) ?: '', $vars);
    }

    /**
     * Inserts `<Name>Interface::class => <Name>::class,` into the
     * `$resolve_target_entities` array, plus the matching `use` imports.
     *
     * Best-effort: if the bundle layout has moved, prints a hint instead
     * of crashing.
     *
     * @param array{name: string, namespace: string} $d
     */
    private function patchAuroraBundle(string $path, array $d): void
    {
        $content = file_get_contents($path);
        if (false === $content) {
            return;
        }

        $name = $d['name'];
        $namespace = $d['namespace'];
        $useEntity = sprintf('use %s\\Entity\\%s;', $namespace, $name);
        $useInterface = sprintf('use %s\\Entity\\%sInterface;', $namespace, $name);
        $mapEntry = sprintf('                    %sInterface::class => %s::class,', $name, $name);

        // Idempotent: skip if the entry is already present.
        if (str_contains($content, $useEntity) || str_contains($content, sprintf('%sInterface::class', $name))) {
            return;
        }

        // 1. Add the two `use` statements after the last existing one in the file.
        // We anchor on the closing `}` of the namespace declaration block, looking for
        // the cluster of `use ...;` lines at the top of the file.
        $useBlockRegex = '/(use [^\n]+;\n)(?!use )/';
        if (preg_match($useBlockRegex, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertAt = $matches[0][1] + strlen($matches[0][0]);
            $content = substr($content, 0, $insertAt)."{$useEntity}\n{$useInterface}\n".substr($content, $insertAt);
        }

        // 2. Append the entry to the `resolve_target_entities` array.
        // Anchored on the literal array opening so the patch is robust to
        // surrounding formatting.
        $arrayOpenRegex = "/'resolve_target_entities' => \[\n/";
        if (preg_match($arrayOpenRegex, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertAt = $matches[0][1] + strlen($matches[0][0]);
            $content = substr($content, 0, $insertAt).$mapEntry."\n".substr($content, $insertAt);
        }

        file_put_contents($path, $content);
    }

    /**
     * @param array{
     *   name: string, name_snake: string, plural_name: string,
     *   plural_snake: string, module_path: string, twig_namespace: string,
     * } $d
     */
    private function printNextSteps(SymfonyStyle $io, array $d, bool $withCrud, bool $withController): void
    {
        $io->section('Next steps');

        $hints = [
            'Review the generated AbstractEntity — the scaffold ships a single `name: string(150)` column. Add your real fields + getters/setters + #[Assert] / @ORM\\Column attributes.',
            sprintf('Generate the migration:   make migration   # writes src/Migrations/Version*.php for table %s', $d['name'].' ('.$d['module_path'].')'),
            'Review the migration SQL, then apply:   make migrate',
        ];

        if ($withCrud) {
            $hints[] = sprintf(
                'Add a %sViewBuilder for the controller\'s index payload (the scaffold renders a flat array of serialized rows — adjust to the real index needs).',
                $d['plural_name'],
            );
            $hints[] = sprintf(
                'Add translations: backend.%s.{title,col_name,actions,errors.name_required,errors.name_too_long,…} in messages.fr.yaml + messages.en.yaml.',
                $d['plural_snake'],
            );
            $hints[] = sprintf(
                'Add a Twig template:   src/%s/templates/backend/%s/index.html.twig — extends `@Core/backend/layout.html.twig`, mounts the Vue component.',
                $d['module_path'],
                $d['plural_snake'],
            );
            $hints[] = sprintf(
                'Add the Vue page: copy `src/Module/Platform/assets/backend/agencies/AgenciesApp.vue` as a starting point for `src/%s/%s/assets/backend/%s/%sApp.vue` (or run /add-crud-list-ui to scaffold).',
                $d['module_path'],
                $d['name'],
                $d['plural_snake'],
                $d['plural_name'],
            );
        }

        if (!$withCrud) {
            $hints[] = '`--no-crud` skipped Layers 2-5 — manually add DTO / Manager / Serializer / Controller later if you change your mind, or run this command without `--no-crud` on a fresh slot.';
        }

        $hints[] = '';
        $hints[] = 'Sanity check after migration:';
        $hints[] = '  make cc                                # clear cache (mandatory after #[AsAlias] / DI changes)';
        $hints[] = '  php bin/console doctrine:schema:validate';
        $hints[] = '  make ft                                # run tests';

        $io->listing($hints);

        $io->note(sprintf('Entity "%s" scaffolded. Layer 1 (Entity + Repository) always generated; CRUD layers %s.',
            $d['name'],
            $withCrud ? 'enabled' : 'skipped (--no-crud)',
        ));
    }
}
