<?php

declare(strict_types=1);

namespace Aurora\Core\Dev\Prerequisite;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

use function extension_loaded;

/**
 * Checks every developer prerequisite listed in
 * `docs/aurora-core/ops/prerequisites.md` and returns a list of
 * {@see PrerequisiteWarning} objects for those that are not satisfied.
 *
 * Designed to be cheap enough to call on every admin page load in the
 * `dev` environment:
 *
 *  - PHP extension checks  → `extension_loaded()` — essentially free.
 *  - Binary checks         → single `exec()` per binary — milliseconds.
 *  - Ollama connectivity   → one HTTP call with a 0.5 s timeout, result
 *    cached in a temp file for 60 s so it does not block every response.
 *
 * Results are also cached in-instance so multiple Twig calls per request
 * (e.g. layout + embed) hit the file cache once at most.
 */
final class DevPrerequisiteChecker
{
    private const int OLLAMA_CACHE_TTL = 60;

    private const string OLLAMA_CACHE_FILE = '/var/cache/aurora_dev_ollama_check.json';

    /** @var list<PrerequisiteWarning>|null */
    private ?array $warnings = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%env(ASSISTANT_OLLAMA_URL)%')]
        private readonly string $ollamaUrl,
        #[Autowire('%env(ASSISTANT_PROVIDER)%')]
        private readonly string $assistantProvider,
        #[Autowire('%env(ASSISTANT_CHAT_MODEL)%')]
        private readonly string $assistantChatModel,
        #[Autowire('%env(ASSISTANT_VISION_MODEL)%')]
        private readonly string $assistantVisionModel,
        #[Autowire('%env(OLLAMA_VISION_MODEL)%')]
        private readonly string $ocrVisionModel,
    ) {}

    /**
     * @return list<PrerequisiteWarning>
     */
    public function getWarnings(): array
    {
        if (null !== $this->warnings) {
            return $this->warnings;
        }

        return $this->warnings = [
            ...$this->checkPhpExtensions(),
            ...$this->checkNodeJs(),
            ...$this->checkOllama(),
        ];
    }

    public function hasWarnings(): bool
    {
        return [] !== $this->getWarnings();
    }

    // ── PHP extensions ────────────────────────────────────────────────

    /** @return list<PrerequisiteWarning> */
    private function checkPhpExtensions(): array
    {
        $required = ['pdo_pgsql', 'intl', 'mbstring', 'gd', 'zip', 'curl'];
        $warnings = [];

        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $warnings[] = new PrerequisiteWarning(
                    message: 'Extension PHP manquante : '.$ext,
                    fix: 'sudo apt install php8.4-'.$ext,
                    level: 'warning',
                );
            }
        }

        return $warnings;
    }

    // ── Node.js ───────────────────────────────────────────────────────

    /** @return list<PrerequisiteWarning> */
    private function checkNodeJs(): array
    {
        if (!$this->execAvailable()) {
            return [];
        }

        $output = [];
        exec('node --version 2>/dev/null', $output, $code);

        if (0 !== $code || [] === $output) {
            return [new PrerequisiteWarning(
                message: 'Node.js introuvable',
                fix: 'Installer depuis nodejs.org ou via nvm (≥ 18 requis)',
                level: 'warning',
            )];
        }

        // Warn if version < 18
        $version = mb_ltrim($output[0], 'v');
        if (version_compare($version, '18.0.0', '<')) {
            return [new PrerequisiteWarning(
                message: sprintf('Node.js %s < 18 requis', $version),
                fix: 'Mettre à jour Node.js via nvm : nvm install 18 && nvm use 18',
                level: 'warning',
            )];
        }

        return [];
    }

    // ── Ollama (cached) ───────────────────────────────────────────────

    /** @return list<PrerequisiteWarning> */
    private function checkOllama(): array
    {
        $status = $this->cachedOllamaStatus();
        $warnings = [];

        if (!$status['running']) {
            $warnings[] = new PrerequisiteWarning(
                message: sprintf('Ollama non joignable (%s)', $this->ollamaUrl),
                fix: 'ollama serve   (ou installer : curl -fsSL https://ollama.ai/install.sh | sh)',
                level: 'info',
            );

            return $warnings;
        }

        $pulledModels = $status['models'];

        // Build the list of required Ollama models from env config.
        // - OCR vision model: always needed (Billing OCR module is always active).
        // - Assistant chat model: only when provider is Ollama.
        // - Assistant vision model: only when provider is Ollama (Anthropic handles vision natively).
        $required = [];
        $required[$this->ocrVisionModel] = 'OCR Billing';

        if ('anthropic' !== $this->assistantProvider) {
            if ('' !== $this->assistantChatModel) {
                $required[$this->assistantChatModel] = 'Assistant IA (chat)';
            }

            if ('' !== $this->assistantVisionModel && $this->assistantVisionModel !== $this->ocrVisionModel) {
                $required[$this->assistantVisionModel] = 'Assistant IA (vision)';
            }
        }

        foreach ($required as $model => $usage) {
            $pulled = false;
            foreach ($pulledModels as $pulledModel) {
                if (str_starts_with($pulledModel, $model)) {
                    $pulled = true;
                    break;
                }
            }

            if (!$pulled) {
                $warnings[] = new PrerequisiteWarning(
                    message: sprintf('Modèle Ollama manquant : %s (%s)', $model, $usage),
                    fix: 'ollama pull '.$model,
                    level: 'info',
                );
            }
        }

        return $warnings;
    }

    /**
     * @return array{running: bool, models: list<string>}
     */
    private function cachedOllamaStatus(): array
    {
        $cacheFile = $this->projectDir.self::OLLAMA_CACHE_FILE;

        if (is_file($cacheFile)) {
            try {
                $cached = json_decode((string) file_get_contents($cacheFile), true, 512, JSON_THROW_ON_ERROR);
                if (isset($cached['ts']) && (time() - (int) $cached['ts']) < self::OLLAMA_CACHE_TTL) {
                    return [
                        'running' => (bool) $cached['running'],
                        'models' => (array) ($cached['models'] ?? []),
                    ];
                }
            } catch (Throwable) {
                // Stale or corrupt cache — continue with fresh check.
            }
        }

        $status = $this->fetchOllamaStatus();

        try {
            @file_put_contents($cacheFile, json_encode([
                'ts' => time(),
                'running' => $status['running'],
                'models' => $status['models'],
            ], JSON_THROW_ON_ERROR));
        } catch (Throwable) {
            // Write failure is non-fatal.
        }

        return $status;
    }

    /**
     * @return array{running: bool, models: list<string>}
     */
    private function fetchOllamaStatus(): array
    {
        $url = mb_rtrim($this->ollamaUrl, '/').'/api/tags';

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 0.5,
                'ignore_errors' => true,
            ],
        ]);

        try {
            $body = @file_get_contents($url, false, $ctx);
            if (false === $body) {
                return ['running' => false, 'models' => []];
            }

            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $models = array_map(
                static fn (array $m): string => (string) ($m['name'] ?? ''),
                $data['models'] ?? [],
            );

            return ['running' => true, 'models' => array_values(array_filter($models))];
        } catch (Throwable) {
            return ['running' => false, 'models' => []];
        }
    }

    private function execAvailable(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = array_map(trim(...), explode(',', ini_get('disable_functions') ?: ''));

        return !in_array('exec', $disabled, true);
    }
}
