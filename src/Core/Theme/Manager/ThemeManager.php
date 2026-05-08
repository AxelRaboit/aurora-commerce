<?php

declare(strict_types=1);

namespace Aurora\Core\Theme\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Theme\Dto\ThemeInputInterface;
use Aurora\Core\Theme\Entity\Theme;
use Aurora\Core\Theme\Entity\ThemeInterface;
use Aurora\Core\Theme\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Filesystem\Path;

#[AsAlias(ThemeManagerInterface::class)]
class ThemeManager implements ThemeManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ThemeRepository $themeRepository,
        protected readonly string $projectDir,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(ThemeInputInterface $input): ThemeInterface
    {
        if ($this->themeRepository->findBySlug($input->getSlug()) instanceof ThemeInterface) {
            throw new InvalidArgumentException(sprintf('slug|%s', 'themes.errors.slug_taken'));
        }

        $theme = $this->createTheme();
        $theme->setSlug($input->getSlug());
        $theme->setName($input->getName());
        $theme->setDescription($input->getDescription());
        $theme->setActive(false);
        $theme->setConfig([]);

        $this->entityManager->persist($theme);
        $this->entityManager->flush();

        $this->auditCreated($theme);

        return $theme;
    }

    public function update(ThemeInterface $theme, ThemeInputInterface $input): void
    {
        $this->applyInput($theme, $input);
        $this->entityManager->flush();

        $this->auditUpdated($theme);
    }

    public function activate(ThemeInterface $theme): void
    {
        $this->entityManager->createQuery('UPDATE '.Theme::class.' t SET t.active = false')->execute();
        $theme->setActive(true);
        $this->entityManager->flush();

        $this->auditLogger->log('core', 'theme.activated', 'Theme', $theme->getId(), $this->auditPayload($theme));
    }

    public function delete(ThemeInterface $theme): void
    {
        if ('default' === $theme->getSlug()) {
            throw new RuntimeException('themes.errors.cannot_delete_default');
        }

        if ($theme->isActive()) {
            throw new RuntimeException('themes.errors.cannot_delete_active');
        }

        $this->auditDeleted($theme);

        $this->entityManager->remove($theme);
        $this->entityManager->flush();
    }

    public function countTemplates(string $slug): int
    {
        $dir = Path::join($this->projectDir, 'templates/Front/themes', $slug);
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

    protected function createTheme(): ThemeInterface
    {
        return new Theme();
    }

    protected function applyInput(ThemeInterface $theme, ThemeInputInterface $input): void
    {
        $theme->setName($input->getName());
        $theme->setDescription($input->getDescription());
        $theme->setConfig($input->getConfig());
    }

    protected function auditCreated(ThemeInterface $theme): void
    {
        $this->auditLogger->log('core', 'theme.created', 'Theme', $theme->getId(), $this->auditPayload($theme));
    }

    protected function auditUpdated(ThemeInterface $theme): void
    {
        $this->auditLogger->log('core', 'theme.updated', 'Theme', $theme->getId(), $this->auditPayload($theme));
    }

    protected function auditDeleted(ThemeInterface $theme): void
    {
        $this->auditLogger->log('core', 'theme.deleted', 'Theme', $theme->getId(), $this->auditPayload($theme));
    }

    protected function auditPayload(ThemeInterface $theme): array
    {
        return ['slug' => $theme->getSlug()];
    }
}
