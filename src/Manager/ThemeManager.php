<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Theme;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ThemeManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ThemeRepository $themeRepository,
        private string $projectDir,
    ) {}

    public function activate(Theme $theme): void
    {
        $this->entityManager->createQuery('UPDATE App\Entity\Theme t SET t.active = false')->execute();
        $theme->setActive(true);
        $this->entityManager->flush();
    }

    public function create(string $slug, string $name, ?string $description): Theme
    {
        $theme = new Theme();
        $theme->setSlug($slug);
        $theme->setName($name);
        $theme->setDescription($description);
        $theme->setActive(false);
        $theme->setConfig([]);

        $this->entityManager->persist($theme);
        $this->entityManager->flush();

        return $theme;
    }

    public function update(Theme $theme, string $name, ?string $description, array $config): void
    {
        $theme->setName($name);
        $theme->setDescription($description);
        $theme->setConfig($config);

        $this->entityManager->flush();
    }

    public function delete(Theme $theme): void
    {
        $this->entityManager->remove($theme);
        $this->entityManager->flush();
    }

    public function countTemplates(string $slug): int
    {
        $dir = sprintf('%s/templates/themes/%s', $this->projectDir, $slug);
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.html.twig')) {
                ++$count;
            }
        }

        return $count;
    }
}
