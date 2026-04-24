<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Locale;
use App\Entity\Post;
use App\Entity\PostTranslation;
use App\Entity\PostType;
use App\Entity\Setting;
use App\Entity\Theme;
use App\Entity\User;
use App\Enum\LocaleEnum;
use App\Enum\PostStatusEnum;
use App\Enum\UserRoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Locales
        $frenchLocale = (new Locale())->setCode(LocaleEnum::French->value)->setName('Français')->setIsDefault(true)->setPosition(0);
        $englishLocale = (new Locale())->setCode(LocaleEnum::English->value)->setName('English')->setPosition(1);
        $manager->persist($frenchLocale);
        $manager->persist($englishLocale);

        // Built-in post types
        $pageType = (new PostType())
            ->setSlug('page')
            ->setLabel('Pages')
            ->setIcon('file')
            ->setHasArchive(false)
            ->setIsBuiltIn(true)
            ->setSupports(['blocks', 'thumbnail', 'excerpt']);

        $articleType = (new PostType())
            ->setSlug('article')
            ->setLabel('Articles')
            ->setIcon('file-text')
            ->setHasArchive(true)
            ->setIsBuiltIn(true)
            ->setSupports(['blocks', 'thumbnail', 'excerpt']);

        $manager->persist($pageType);
        $manager->persist($articleType);

        // Default theme
        $theme = (new Theme())
            ->setSlug('default')
            ->setName('Default')
            ->setDescription('Thème par défaut de Velox')
            ->setActive(true);
        $manager->persist($theme);

        // Settings
        $settings = [
            ['site_name', 'Mon Site', 'string', 'general'],
            ['site_description', 'Propulsé par Velox CMS', 'string', 'general'],
            ['default_locale', LocaleEnum::French->value, 'string', 'general'],
            ['posts_per_page', '10', 'int', 'reading'],
        ];

        foreach ($settings as [$key, $value, $type, $group]) {
            $setting = (new Setting())->setKey($key)->setValue($value)->setType($type)->setGroup($group);
            $manager->persist($setting);
        }

        // Admin user
        $user = new User();
        $user->setEmail('admin@velox.app')
             ->setName('Admin User')
             ->setRoles([UserRoleEnum::Admin->value])
             ->setPassword($this->hasher->hashPassword($user, 'password'));
        $manager->persist($user);

        // Sample page
        $homePage = (new Post())->setPostType($pageType)->setStatus(PostStatusEnum::Published);
        $homePageFrench = (new PostTranslation())
            ->setPost($homePage)
            ->setLocale(LocaleEnum::French->value)
            ->setTitle('Accueil')
            ->setSlug('accueil')
            ->setBlocks([
                ['type' => 'heading', 'data' => ['text' => 'Bienvenue sur Velox', 'level' => 1]],
                ['type' => 'paragraph', 'data' => ['text' => 'Votre CMS moderne propulsé par Symfony et Vue 3.']],
            ]);
        $homePageEnglish = (new PostTranslation())
            ->setPost($homePage)
            ->setLocale(LocaleEnum::English->value)
            ->setTitle('Home')
            ->setSlug('home')
            ->setBlocks([
                ['type' => 'heading', 'data' => ['text' => 'Welcome to Velox', 'level' => 1]],
                ['type' => 'paragraph', 'data' => ['text' => 'Your modern CMS powered by Symfony and Vue 3.']],
            ]);
        $manager->persist($homePage);
        $manager->persist($homePageFrench);
        $manager->persist($homePageEnglish);

        $manager->flush();
    }
}
