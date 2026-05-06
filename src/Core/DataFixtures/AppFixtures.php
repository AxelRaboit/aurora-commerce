<?php

declare(strict_types=1);

namespace Aurora\Core\DataFixtures;

use Aurora\Core\Locale\Entity\Locale;
use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Setting\Entity\Setting;
use Aurora\Core\Theme\Entity\Theme;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Enum\UserTypeEnum;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Service\PostTextExtractor;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly PostTextExtractor $textExtractor,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Locales
        $frenchLocale = new Locale()->setCode(LocaleEnum::French->value)->setName('Français')->setIsDefault(true)->setPosition(0);
        $englishLocale = new Locale()->setCode(LocaleEnum::English->value)->setName('English')->setPosition(1);
        $manager->persist($frenchLocale);
        $manager->persist($englishLocale);

        // Built-in post types
        $pageType = new PostType()
            ->setSlug('page')
            ->setLabel('Pages')
            ->setIcon('file')
            ->setHasArchive(false)
            ->setIsBuiltIn(true)
            ->setSupports(['blocks', 'thumbnail', 'excerpt']);

        $articleType = new PostType()
            ->setSlug('article')
            ->setLabel('Articles')
            ->setIcon('file-text')
            ->setHasArchive(true)
            ->setIsBuiltIn(true)
            ->setSupports(['blocks', 'thumbnail', 'excerpt']);

        $manager->persist($pageType);
        $manager->persist($articleType);

        // Built-in taxonomies
        $taxonomyLabels = [
            'tag' => ['fr' => 'Étiquette', 'en' => 'Tag'],
            'category' => ['fr' => 'Catégorie', 'en' => 'Category'],
        ];

        foreach ($taxonomyLabels as $slug => $labels) {
            $taxonomy = new Taxonomy()
                ->setSlug($slug)
                ->setHierarchical('category' === $slug)
                ->setIsBuiltIn(true);

            foreach ($labels as $locale => $label) {
                $taxonomy->translate($locale)->setLabel($label);
            }

            $pageType->addTaxonomy($taxonomy);
            $articleType->addTaxonomy($taxonomy);

            $manager->persist($taxonomy);

            if ('tag' === $slug) {
                foreach (['Nouveauté' => 'nouveaute', 'Tutoriel' => 'tutoriel'] as $name => $termSlug) {
                    $term = new TaxonomyTerm()->setTaxonomy($taxonomy);
                    foreach (array_keys($labels) as $locale) {
                        $term->translate($locale)->setName($name)->setSlug($termSlug);
                    }

                    $manager->persist($term);
                }
            }
        }

        // Default theme
        $theme = new Theme()
            ->setSlug('default')
            ->setName('Default')
            ->setDescription('Thème par défaut de Aurora')
            ->setActive(true);
        $manager->persist($theme);

        // Settings
        $settings = [
            ['site_name', 'Aurora', 'string', 'general'],
            ['site_description', 'Propulsé par Aurora', 'string', 'general'],
            ['default_locale', LocaleEnum::French->value, 'string', 'general'],
            ['posts_per_page', '10', 'int', 'reading'],
        ];

        foreach ($settings as [$key, $value, $type, $group]) {
            $setting = new Setting()->setKey($key)->setValue($value)->setType($type)->setGroup($group);
            $manager->persist($setting);
        }

        // Admin user (backend)
        $adminUser = new User();
        $adminUser->setEmail('dev@aurora.app')
             ->setName('Admin User')
             ->setRoles([UserRoleEnum::Dev->value])
             ->setPassword($this->hasher->hashPassword($adminUser, 'password'));
        $manager->persist($adminUser);

        // Frontend user — same email, accessible via front login
        $frontUser = new User();
        $frontUser->setEmail('dev@aurora.app')
             ->setName('Admin User')
             ->setType(UserTypeEnum::Frontend)
             ->setRoles([UserRoleEnum::Dev->value])
             ->setPassword($this->hasher->hashPassword($frontUser, 'password'));
        $manager->persist($frontUser);

        // Sample page
        $homePage = new Post()->setPostType($pageType)->setStatus(PostStatusEnum::Published);
        $homePageFrench = new PostTranslation()
            ->setPost($homePage)
            ->setLocale(LocaleEnum::French->value)
            ->setTitle('Accueil')
            ->setSlug('accueil')
            ->setBlocks([
                ['type' => 'heading', 'data' => ['text' => 'Bienvenue sur Aurora', 'level' => 1]],
                ['type' => 'paragraph', 'data' => ['text' => 'Votre CMS moderne propulsé par Symfony et Vue 3.']],
            ]);
        $homePageEnglish = new PostTranslation()
            ->setPost($homePage)
            ->setLocale(LocaleEnum::English->value)
            ->setTitle('Home')
            ->setSlug('home')
            ->setBlocks([
                ['type' => 'heading', 'data' => ['text' => 'Welcome to Aurora', 'level' => 1]],
                ['type' => 'paragraph', 'data' => ['text' => 'Your modern CMS powered by Symfony and Vue 3.']],
            ]);
        $homePageFrench->setSearchContent($this->textExtractor->extract($homePageFrench));
        $homePageEnglish->setSearchContent($this->textExtractor->extract($homePageEnglish));

        $manager->persist($homePage);
        $manager->persist($homePageFrench);
        $manager->persist($homePageEnglish);

        $manager->flush();
    }
}
