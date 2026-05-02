<?php

declare(strict_types=1);

namespace Aurora\Core\Theme\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Theme\DTO\ThemeInput;
use Aurora\Core\Theme\Entity\Theme;
use Aurora\Core\Theme\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final readonly class ThemeManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ThemeRepository $themeRepository,
        private string $projectDir,
        private AuditLogger $auditLogger,
    ) {}

    public function create(ThemeInput $input): Theme
    {
        if ($this->themeRepository->findBySlug($input->slug) instanceof Theme) {
            throw new InvalidArgumentException(sprintf('slug|%s', 'themes.errors.slug_taken'));
        }

        $theme = new Theme();
        $theme->setSlug($input->slug);
        $theme->setName($input->name);
        $theme->setDescription($input->description);
        $theme->setActive(false);
        $theme->setConfig([]);

        $this->entityManager->persist($theme);
        $this->entityManager->flush();

        $this->auditLogger->log('core', 'theme.created', 'Theme', $theme->getId(), ['slug' => $theme->getSlug()]);

        return $theme;
    }

    public function update(Theme $theme, ThemeInput $input): void
    {
        $theme->setName($input->name);
        $theme->setDescription($input->description);
        $theme->setConfig($input->config);

        $this->entityManager->flush();

        $this->auditLogger->log('core', 'theme.updated', 'Theme', $theme->getId(), ['slug' => $theme->getSlug()]);
    }

    public function activate(Theme $theme): void
    {
        $this->entityManager->createQuery('UPDATE '.Theme::class.' t SET t.active = false')->execute();
        $theme->setActive(true);
        $this->entityManager->flush();

        $this->auditLogger->log('core', 'theme.activated', 'Theme', $theme->getId(), ['slug' => $theme->getSlug()]);
    }

    public function delete(Theme $theme): void
    {
        if ('default' === $theme->getSlug()) {
            throw new RuntimeException('themes.errors.cannot_delete_default');
        }

        if ($theme->isActive()) {
            throw new RuntimeException('themes.errors.cannot_delete_active');
        }

        $id = $theme->getId();
        $slug = $theme->getSlug();
        $this->entityManager->remove($theme);
        $this->entityManager->flush();

        $this->auditLogger->log('core', 'theme.deleted', 'Theme', $id, ['slug' => $slug]);
    }

    public function countTemplates(string $slug): int
    {
        $dir = sprintf('%s/templates/Front/themes/%s', $this->projectDir, $slug);
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
            if ($file->isFile() && str_ends_with((string) $file->getFilename(), '.html.twig')) {
                ++$count;
            }
        }

        return $count;
    }
}
