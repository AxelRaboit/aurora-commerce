<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Command;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Dumps Symfony YAML translations to JSON files consumed by vue-i18n at runtime.
 *
 * - Scans messages.{locale}.yaml in every module translations directory and deep-merges them.
 * - Converts Symfony-style `%var%` placeholders to vue-i18n-style `{var}`.
 * - Writes assets/locales/generated/{locale}.json (gitignored).
 *
 * `assets/i18n.js` deep-merges these generated catalogues with manual JS source files
 * (assets/locales/source/{locale}.js), with YAML winning on conflicts. This means:
 *   - Shared keys (used in both Twig and Vue) live ONLY in YAML — single source of truth.
 *   - Vue-only keys (admin form labels, etc.) stay in the JS source files.
 */
#[AsCommand(name: 'app:translations:dump-js', description: 'Dump Symfony YAML translations as JSON for vue-i18n')]
final class DumpJsTranslationsCommand extends Command
{
    private const array SOURCE_DIRS = [
        'src/Core/translations',
        'src/Module/Editorial/translations',
        'src/Module/Crm/translations',
        'src/Module/Erp/translations',
        'src/Module/Ecommerce/translations',
        'src/Module/Photo/translations',
        'src/Module/Billing/translations',
    ];

    private const string OUTPUT_DIR = 'assets/locales/generated';

    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $outputDir = $this->projectDir.'/'.self::OUTPUT_DIR;

        if (!is_dir($outputDir) && !mkdir($outputDir, 0o775, true) && !is_dir($outputDir)) {
            $io->error('Cannot create output directory: '.$outputDir);

            return Command::FAILURE;
        }

        foreach (LocaleEnum::values() as $locale) {
            $merged = [];
            $sourcesFound = 0;

            foreach (self::SOURCE_DIRS as $sourceDir) {
                $sourcePath = sprintf('%s/%s/messages.%s.yaml', $this->projectDir, $sourceDir, $locale);
                if (!is_file($sourcePath)) {
                    continue;
                }

                $tree = Yaml::parseFile($sourcePath) ?? [];
                $merged = $this->deepMerge($merged, is_array($tree) ? $tree : []);
                ++$sourcesFound;
            }

            if (0 === $sourcesFound) {
                $io->warning(sprintf("No translation file found for locale '%s'", $locale));
                continue;
            }

            $converted = $this->convertPlaceholders($merged);

            $outPath = sprintf('%s/%s.json', $outputDir, $locale);
            file_put_contents($outPath, json_encode($converted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
            $io->writeln(sprintf('  <info>✓</info> %s (%d keys from %d module(s))', basename($outPath), $this->countLeaves($converted), $sourcesFound));
        }

        $io->success('Translations dumped. vue-i18n will auto-pick them on next dev/build.');

        return Command::SUCCESS;
    }

    /** Recursively merges $source into $target. Source wins on scalar conflicts. */
    private function deepMerge(array $target, array $source): array
    {
        foreach ($source as $key => $value) {
            if (is_array($value) && isset($target[$key]) && is_array($target[$key])) {
                $target[$key] = $this->deepMerge($target[$key], $value);
            } else {
                $target[$key] = $value;
            }
        }

        return $target;
    }

    /**
     * Recursively prepares messages for vue-i18n consumption:
     *  - Symfony `%var%` placeholders → `{var}`
     *  - Bare `@` characters (e.g. in `you@example.com` placeholders) escaped as `{'@'}` so
     *    vue-i18n's linked-message parser doesn't treat them as `@:other.key` syntax.
     */
    private function convertPlaceholders(mixed $value): mixed
    {
        if (is_string($value)) {
            $converted = preg_replace('/%([A-Za-z_]\w*)%/', '{$1}', $value) ?? $value;

            return str_replace('@', "{'@'}", $converted);
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->convertPlaceholders($v);
            }

            return $out;
        }

        return $value;
    }

    private function countLeaves(array $tree): int
    {
        $count = 0;
        foreach ($tree as $v) {
            $count += is_array($v) ? $this->countLeaves($v) : 1;
        }

        return $count;
    }
}
