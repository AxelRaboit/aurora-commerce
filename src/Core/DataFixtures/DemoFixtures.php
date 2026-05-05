<?php

declare(strict_types=1);

namespace Aurora\Core\DataFixtures;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Menu\Entity\Menu;
use Aurora\Core\Menu\Entity\MenuItem;
use Aurora\Core\Menu\Entity\MenuItemTranslation;
use Aurora\Core\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Entity\OrderLine;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Enum\CommentStatusEnum;
use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormField;
use Aurora\Module\Editorial\Form\Entity\FormFieldTranslation;
use Aurora\Module\Editorial\Form\Entity\FormSubmission;
use Aurora\Module\Editorial\Form\Entity\FormTranslation;
use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategory;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryFinalization;
use Aurora\Module\Photo\Gallery\Entity\GalleryItem;
use Aurora\Module\Photo\Gallery\Entity\GalleryItemComment;
use Aurora\Module\Photo\Gallery\Entity\GalleryPick;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Comprehensive demo fixtures covering all Aurora modules.
 *
 * Scenario: "Aurora Tech" — a French software company using the full platform.
 * Run: php bin/console doctrine:fixtures:load --group=demo
 */
class DemoFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private const string LOREM = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        #[Autowire('%app.upload_dir%')]
        private readonly string $uploadDir,
        private readonly Filesystem $fs = new Filesystem(),
    ) {}

    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function getDependencies(): array
    {
        return [AppFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);

        $users = $this->createUsers($manager);
        $media = $this->createMedia($manager);
        $postType = $manager->getRepository(PostType::class)->findOneBy(['slug' => 'article']);

        $posts = $this->createEditorial($manager, $postType, $media, $users);
        $this->createComments($manager, $posts);
        $this->createForms($manager);
        $this->createTaxonomies($manager, $postType);
        [$companies, $contacts] = $this->createCrm($manager, $users);
        $products = $this->createErp($manager, $media);
        $listings = $this->createEcommerce($manager, $products, $media, $users);
        $this->createBilling($manager, $media);
        $this->createPhoto($manager, $media, $users, $contacts);
        $this->createGed($manager, $media);
        $this->createMenuItems($manager);

        $manager->flush();
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    /** @return User[] */
    private function createUsers(EntityManagerInterface $em): array
    {
        $users = [];

        $defs = [
            [
                'email' => 'marie.dupont@aurora.app',
                'name' => 'Marie Dupont',
                'role' => UserRoleEnum::Admin,
                'privileges' => [],
                'mood' => 'Responsable des opérations 🚀',
            ],
            [
                'email' => 'jean.martin@aurora.app',
                'name' => 'Jean Martin',
                'role' => UserRoleEnum::User,
                'privileges' => ['crm.contacts.view', 'crm.contacts.create', 'crm.contacts.edit', 'crm.companies.manage', 'crm.deals.manage', 'ged.documents.manage', 'ged.categories.manage'],
                'mood' => 'Commercial senior',
            ],
            [
                'email' => 'sophie.bernard@aurora.app',
                'name' => 'Sophie Bernard',
                'role' => UserRoleEnum::User,
                'privileges' => ['editorial.posts.view', 'editorial.posts.manage', 'editorial.forms.manage', 'editorial.comments.manage', 'core.media.view', 'core.media.manage'],
                'mood' => 'Rédactrice en chef ✍️',
            ],
            [
                'email' => 'thomas.petit@aurora.app',
                'name' => 'Thomas Petit',
                'role' => UserRoleEnum::User,
                'privileges' => ['ecommerce.listings.view', 'ecommerce.listings.create', 'ecommerce.listings.edit', 'ecommerce.orders.view', 'ecommerce.orders.manage', 'billing.invoices.view', 'billing.invoices.create', 'billing.invoices.edit', 'billing.tiers.manage', 'erp.products.view', 'erp.products.create', 'erp.products.edit'],
                'mood' => 'Responsable boutique & facturation',
            ],
        ];

        foreach ($defs as $def) {
            $user = new User();
            $user->setEmail($def['email'])
                 ->setName($def['name'])
                 ->setRoles([$def['role']->value])
                 ->setPrivileges($def['privileges'])
                 ->setMoodMessage($def['mood'])
                 ->setLocale(LocaleEnum::French)
                 ->setPassword($this->hasher->hashPassword($user, 'password'));
            $em->persist($user);
            $users[] = $user;
        }

        return $users;
    }

    // ── Media ─────────────────────────────────────────────────────────────────

    /** @return Media[] */
    private function createMedia(EntityManagerInterface $em): array
    {
        $month = new DateTimeImmutable()->format('Y-m');
        $destDir = $this->uploadDir.'/media/'.$month;
        $this->fs->mkdir($destDir);

        $sourceDir = dirname(__DIR__, 3).'/test_files';
        $defs = [
            ['src' => 'images/ai-generated-8359510_1280-1816135935.jpg', 'name' => 'hero-banner.jpg',      'original' => 'hero-banner.jpg',    'mime' => 'image/jpeg', 'w' => 1280, 'h' => 853],
            ['src' => 'images/canadian-flag-canada-maple-country-wallpaper-1506073439.jpg', 'name' => 'landscape.jpg', 'original' => 'landscape.jpg', 'mime' => 'image/jpeg', 'w' => 1280, 'h' => 720],
            ['src' => 'images/me.jpg',           'name' => 'portrait-team.jpg',  'original' => 'portrait-team.jpg',  'mime' => 'image/jpeg', 'w' => 800,  'h' => 1000],
            ['src' => 'images/previous_job.jpg', 'name' => 'office-setup.jpg',   'original' => 'office-setup.jpg',   'mime' => 'image/jpeg', 'w' => 1200, 'h' => 800],
            ['src' => 'videos/sample-30s-720p.mp4',  'name' => 'demo-video.mp4',   'original' => 'demo-video.mp4',   'mime' => 'video/mp4',  'w' => 1280, 'h' => 720],
            ['src' => 'files/invoices/Commercial-Invoice-Sample.webp', 'name' => 'invoice-sample.webp', 'original' => 'invoice-sample.webp', 'mime' => 'image/webp', 'w' => 0, 'h' => 0],
        ];

        $media = [];
        foreach ($defs as $def) {
            $src = $sourceDir.'/'.$def['src'];
            if (!file_exists($src)) {
                continue;
            }

            $dest = $destDir.'/'.$def['name'];
            $this->fs->copy($src, $dest, true);

            $m = new Media();
            $m->setFilename($def['name'])
              ->setOriginalName($def['original'])
              ->setMimeType($def['mime'])
              ->setSize((int) filesize($dest))
              ->setPath('media/'.$month.'/'.$def['name'])
              ->setVariants([]);

            if ($def['w'] > 0) {
                $m->setWidth($def['w'])->setHeight($def['h']);
            }

            $em->persist($m);
            $media[] = $m;
        }

        return $media;
    }

    // ── Editorial ─────────────────────────────────────────────────────────────

    /** @param array<int, array{type: string, data: array<string, mixed>}> $blocks */
    private function blocksText(array $blocks): string
    {
        $parts = [];
        foreach ($blocks as $b) {
            if (isset($b['data']['text']) && is_string($b['data']['text'])) {
                $parts[] = $b['data']['text'];
            }
        }

        return implode(' ', $parts);
    }

    /** @return Post[] */
    private function createEditorial(EntityManagerInterface $em, ?PostType $postType, array $media, array $users): array
    {
        if (!$postType instanceof PostType) {
            return [];
        }

        $createdPosts = [];

        /** @var array<int, array{fr: array{title: string, slug: string, excerpt: string, blocks: array<int, array{type: string, data: array<string, mixed>}>}, en: array{title: string, slug: string, excerpt: string, blocks: array<int, array{type: string, data: array<string, mixed>}>}, media: ?Media}> $posts */
        $posts = [
            [
                'fr' => [
                    'title' => 'Bienvenue sur Aurora — La suite métier tout-en-un',
                    'slug' => 'bienvenue-sur-aurora',
                    'excerpt' => 'Découvrez Aurora, la plateforme qui unifie CRM, ERP, e-commerce, facturation et gestion documentaire.',
                    'blocks' => [
                        ['type' => 'heading',   'data' => ['text' => 'Une plateforme pour tout gérer', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                        ['type' => 'heading',   'data' => ['text' => 'CRM & Gestion commerciale', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Gérez vos contacts, entreprises et opportunités depuis une interface unifiée. Suivez chaque deal de la prospection à la signature. '.self::LOREM]],
                        ['type' => 'heading',   'data' => ['text' => 'E-commerce intégré', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Publiez votre catalogue, gérez les commandes et les paiements Stripe sans quitter votre espace admin. '.self::LOREM]],
                    ],
                ],
                'en' => [
                    'title' => 'Welcome to Aurora — The All-in-One Business Suite',
                    'slug' => 'welcome-to-aurora',
                    'excerpt' => 'Discover Aurora, the platform that unifies CRM, ERP, e-commerce, billing and document management.',
                    'blocks' => [
                        ['type' => 'heading',   'data' => ['text' => 'One platform to manage everything', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ],
                ],
                'media' => $media[0] ?? null,
            ],
            [
                'fr' => [
                    'title' => 'Les meilleures pratiques du développement web en 2025',
                    'slug' => 'meilleures-pratiques-developpement-web-2025',
                    'excerpt' => 'Symfony, Vue.js, Vite, Tailwind CSS — le stack moderne pour construire des applications web performantes.',
                    'blocks' => [
                        ['type' => 'heading',   'data' => ['text' => 'Le stack moderne en 2025', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                        ['type' => 'heading',   'data' => ['text' => 'Symfony 7 & PHP 8.4', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Les attributs PHP 8.4, les readonly properties et les énumérations font de PHP un langage moderne et expressif. '.self::LOREM]],
                        ['type' => 'heading',   'data' => ['text' => 'Vue.js 3 & Composition API', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                        ['type' => 'heading',   'data' => ['text' => 'Tailwind CSS v4', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Le utility-first CSS framework repensé avec une configuration CSS-native et des performances de build imbattables. '.self::LOREM]],
                    ],
                ],
                'en' => [
                    'title' => 'Web Development Best Practices in 2025',
                    'slug' => 'web-development-best-practices-2025',
                    'excerpt' => 'Symfony, Vue.js, Vite, Tailwind CSS — the modern stack for performant web applications.',
                    'blocks' => [
                        ['type' => 'heading',   'data' => ['text' => 'The modern stack in 2025', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ],
                ],
                'media' => $media[1] ?? null,
            ],
            [
                'fr' => [
                    'title' => 'Comment Aurora transforme la gestion de votre entreprise',
                    'slug' => 'aurora-transforme-gestion-entreprise',
                    'excerpt' => 'Retour d\'expérience après 6 mois d\'utilisation — témoignage d\'un dirigeant de PME.',
                    'blocks' => [
                        ['type' => 'heading',   'data' => ['text' => '6 mois avec Aurora : notre bilan', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Avant Aurora, notre équipe jonglait entre 4 outils différents pour gérer les clients, les stocks, les commandes et la facturation. '.self::LOREM]],
                        ['type' => 'heading',   'data' => ['text' => 'Ce qui a changé', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Le premier bénéfice immédiat : la centralisation des données. Un seul endroit pour trouver l\'historique d\'un client, ses commandes, ses factures. '.self::LOREM]],
                        ['type' => 'heading',   'data' => ['text' => 'Le module GED', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ],
                ],
                'en' => [
                    'title' => 'How Aurora Transforms Your Business Management',
                    'slug' => 'aurora-transforms-business-management',
                    'excerpt' => 'A 6-month experience report — testimonial from an SME founder.',
                    'blocks' => [
                        ['type' => 'heading',   'data' => ['text' => '6 months with Aurora: our review', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ],
                ],
                'media' => $media[2] ?? null,
            ],
        ];

        foreach ($posts as $idx => $def) {
            $post = new Post();
            $post->setPostType($postType)
                 ->setStatus(PostStatusEnum::Published)
                 ->setPublishedAt(new DateTimeImmutable('-'.($idx + 1).' weeks'))
                 ->setFeaturedMedia($def['media'] ?? null);

            foreach (['fr', 'en'] as $locale) {
                $loc = $def[$locale];
                $tr = new PostTranslation();
                $tr->setPost($post)
                   ->setLocale($locale)
                   ->setTitle($loc['title'])
                   ->setSlug($loc['slug'])
                   ->setBlocks($loc['blocks'])
                   ->setSearchContent($this->blocksText($loc['blocks']));

                if ('fr' === $locale) {
                    $tr->setMetaDescription($loc['excerpt']);
                }

                if ('fr' === $locale && isset($def['media'])) {
                    $tr->setOgImage($def['media']);
                }

                $em->persist($tr);
            }

            $em->persist($post);
            $createdPosts[] = $post;
        }

        // Extra posts with rich EditorJS content including images
        $img0 = isset($media[0]) ? $media[0]->getPublicUrl() : '';
        $img1 = isset($media[1]) ? $media[1]->getPublicUrl() : '';
        $img2 = isset($media[2]) ? $media[2]->getPublicUrl() : '';
        $img3 = isset($media[3]) ? $media[3]->getPublicUrl() : '';

        /** @var array<int, array{title: string, slug: string, media: ?Media, ago: string, blocks: array<int, array{type: string, data: array<string, mixed>}>}> $extraDefs */
        $extraDefs = [
            [
                'title' => 'Retour sur Aurora Tech Day 2025',
                'slug' => 'aurora-tech-day-2025',
                'media' => $media[3] ?? null,
                'ago' => '3 days',
                'blocks' => [
                    ['type' => 'heading',   'data' => ['text' => 'Une journée dédiée à l\'innovation', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => 'Plus de 200 développeurs et dirigeants réunis pour découvrir les nouveautés Aurora. '.self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img3, 'width' => 1200, 'height' => 800], 'caption' => 'Aurora Tech Day 2025 — Grande salle des conférences', 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                    ['type' => 'heading',   'data' => ['text' => 'Les annonces phares', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img0, 'width' => 1280, 'height' => 853], 'caption' => 'Démonstration en direct du module GED', 'withBorder' => false, 'withBackground' => false, 'stretched' => false]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            [
                'title' => 'Roadmap Aurora 2025-2026 : les grandes orientations',
                'slug' => 'roadmap-aurora-2025-2026',
                'media' => $media[0] ?? null,
                'ago' => '10 days',
                'blocks' => [
                    ['type' => 'heading',   'data' => ['text' => 'Notre vision pour les 18 prochains mois', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => 'Nous avons écouté vos retours. Voici les priorités qui guideront le développement d\'Aurora jusqu\'en 2026. '.self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img0, 'width' => 1280, 'height' => 853], 'caption' => 'Feuille de route Aurora 2025-2026', 'withBorder' => false, 'withBackground' => true, 'stretched' => false]],
                    ['type' => 'heading',   'data' => ['text' => 'Module Suivi & Workflow', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'heading',   'data' => ['text' => 'Intelligence artificielle intégrée', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            [
                'title' => 'Tutoriel : créer votre premier module client Aurora',
                'slug' => 'tutoriel-premier-module-client',
                'media' => $media[1] ?? null,
                'ago' => '5 days',
                'blocks' => [
                    ['type' => 'heading',   'data' => ['text' => 'Prérequis', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => 'Aurora est installé, vous avez un projet client. Maintenant, créons un module sur-mesure. '.self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img1, 'width' => 1280, 'height' => 720], 'caption' => "Structure d'un module Aurora", 'withBorder' => true, 'withBackground' => false, 'stretched' => false]],
                    ['type' => 'heading',   'data' => ['text' => 'Étape 1 : Créer l\'entité', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'heading',   'data' => ['text' => 'Étape 2 : Le composant Vue', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img2, 'width' => 800, 'height' => 1000], 'caption' => 'Le résultat final dans l\'admin', 'withBorder' => false, 'withBackground' => false, 'stretched' => false]],
                ],
            ],
            [
                'title' => 'Aurora & l\'IA : automatisez vos processus métier',
                'slug' => 'aurora-ia-automatisation-processus',
                'media' => $media[2] ?? null,
                'ago' => '2 days',
                'blocks' => [
                    ['type' => 'heading',   'data' => ['text' => 'L\'IA au service de la productivité', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img2, 'width' => 800, 'height' => 1000], 'caption' => 'Interface Aurora avec suggestions IA', 'withBorder' => false, 'withBackground' => false, 'stretched' => false]],
                    ['type' => 'heading',   'data' => ['text' => 'OCR et extraction de données', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            [
                'title' => 'Guide : Sécuriser Aurora en production',
                'slug' => 'guide-securiser-aurora-production',
                'media' => $media[0] ?? null,
                'ago' => '15 days',
                'blocks' => [
                    ['type' => 'heading',   'data' => ['text' => 'Checklist sécurité production', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img0, 'width' => 1280, 'height' => 853], 'caption' => 'Dashboard monitoring Aurora', 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
        ];
        foreach ($extraDefs as $extra) {
            $p = new Post();
            $p->setPostType($postType)
              ->setStatus(PostStatusEnum::Published)
              ->setPublishedAt(new DateTimeImmutable('-'.$extra['ago']))
              ->setFeaturedMedia($extra['media'] ?? null);
            $tr = new PostTranslation();
            $tr->setPost($p)->setLocale('fr')->setTitle($extra['title'])->setSlug($extra['slug'])
               ->setBlocks($extra['blocks'])
               ->setSearchContent($this->blocksText($extra['blocks']));
            if (null !== $extra['media']) {
                $tr->setOgImage($extra['media']);
            }

            $em->persist($tr);
            $em->persist($p);
            $createdPosts[] = $p;
        }

        return $createdPosts;
    }

    // ── Comments ──────────────────────────────────────────────────────────────

    /** @param Post[] $posts */
    private function createComments(EntityManagerInterface $em, array $posts): void
    {
        $commentData = [
            ['name' => 'Pierre Dupont',    'email' => 'pierre.dupont@example.com', 'status' => CommentStatusEnum::Approved, 'text' => 'Article très intéressant ! J\'ai particulièrement apprécié la partie sur les meilleures pratiques. Merci pour ce partage.'],
            ['name' => 'Camille Martin',   'email' => 'camille.martin@example.com', 'status' => CommentStatusEnum::Approved, 'text' => 'Exactement ce que je cherchais. On utilise Aurora depuis 3 mois et les résultats sont au rendez-vous.'],
            ['name' => 'François Petit',   'email' => 'f.petit@company.fr',        'status' => CommentStatusEnum::Approved, 'text' => "Super contenu ! Question : est-ce qu'Aurora supporte le multi-tenant ? Merci d'avance."],
            ['name' => 'Alice Bernard',    'email' => 'alice@startup.io',           'status' => CommentStatusEnum::Pending,  'text' => 'Bonne introduction, mais j\'aurais aimé plus de détails sur la partie déploiement.'],
            ['name' => 'Marc Fontaine',    'email' => 'marc.fontaine@mail.com',     'status' => CommentStatusEnum::Approved, 'text' => 'Le module GED est vraiment bien pensé. On l\'a intégré à nos processus RH.'],
            ['name' => 'Sophie Leblond',   'email' => 'sophie.l@example.org',       'status' => CommentStatusEnum::Pending,  'text' => 'Avez-vous prévu un module de gestion de projet ? Ce serait un excellent complément !'],
        ];

        foreach ($posts as $postIdx => $post) {
            // Assign 2-3 comments per post, cycling through commentData
            $count = (0 === $postIdx % 2) ? 3 : 2;
            for ($i = 0; $i < $count; ++$i) {
                $cd = $commentData[($postIdx * 2 + $i) % count($commentData)];
                $c = new Comment();
                $c->setPost($post)
                  ->setAuthorName($cd['name'])
                  ->setAuthorEmail($cd['email'])
                  ->setContent($cd['text'])
                  ->setStatus($cd['status']);
                $em->persist($c);
            }
        }
    }

    // ── Forms ─────────────────────────────────────────────────────────────────

    private function createForms(EntityManagerInterface $em): void
    {
        $formDefs = [
            [
                'slug' => 'contact',
                'fr' => 'Nous contacter',
                'en' => 'Contact Us',
                'fields' => [
                    ['type' => FormFieldTypeEnum::Text,     'fr' => 'Nom complet',      'en' => 'Full name',      'ph_fr' => 'Jean Dupont',              'ph_en' => 'John Doe',             'req' => true],
                    ['type' => FormFieldTypeEnum::Email,    'fr' => 'Adresse email',    'en' => 'Email address',  'ph_fr' => 'jean@exemple.fr',          'ph_en' => 'john@example.com',     'req' => true],
                    ['type' => FormFieldTypeEnum::Tel,      'fr' => 'Téléphone',        'en' => 'Phone',          'ph_fr' => '+33 6 00 00 00 00',       'ph_en' => '+1 555 000 0000',       'req' => false],
                    ['type' => FormFieldTypeEnum::Select,   'fr' => 'Sujet',            'en' => 'Subject',        'ph_fr' => 'Choisissez un sujet',     'ph_en' => 'Choose a subject',      'req' => true,
                        'opts_fr' => ['Demande commerciale', 'Support technique', 'Partenariat', 'Autre'],
                        'opts_en' => ['Sales inquiry', 'Technical support', 'Partnership', 'Other']],
                    ['type' => FormFieldTypeEnum::Textarea, 'fr' => 'Message',          'en' => 'Message',        'ph_fr' => 'Votre message…',          'ph_en' => 'Your message…',         'req' => true],
                ],
                'submissions' => [
                    ['name' => 'Pierre Dubois', 'email' => 'pierre.dubois@tech-innovation.fr', 'subject' => 'Demande commerciale', 'message' => 'Bonjour, je souhaite un devis pour une licence CRM 10 utilisateurs.'],
                    ['name' => 'Camille Leroy', 'email' => 'c.leroy@biomed-france.com',        'subject' => 'Support technique',   'message' => 'Problème de synchronisation des données. Pouvez-vous nous aider ?'],
                ],
            ],
            [
                'slug' => 'newsletter',
                'fr' => 'Inscription Newsletter',
                'en' => 'Newsletter Sign-up',
                'fields' => [
                    ['type' => FormFieldTypeEnum::Email, 'fr' => 'Votre email', 'en' => 'Your email', 'ph_fr' => 'vous@exemple.fr', 'ph_en' => 'you@example.com', 'req' => true],
                    ['type' => FormFieldTypeEnum::Text,  'fr' => 'Prénom',      'en' => 'First name', 'ph_fr' => 'Prénom',          'ph_en' => 'First name',      'req' => false],
                    ['type' => FormFieldTypeEnum::Checkbox, 'fr' => "J'accepte de recevoir des communications Aurora", 'en' => 'I agree to receive Aurora communications', 'ph_fr' => '', 'ph_en' => '', 'req' => true],
                ],
                'submissions' => [
                    ['email' => 'fan1@example.com',  'prenom' => 'Julie'],
                    ['email' => 'fan2@example.com',  'prenom' => 'Marc'],
                    ['email' => 'fan3@example.com',  'prenom' => 'Sophie'],
                ],
            ],
        ];

        foreach ($formDefs as $fd) {
            $form = new Form();
            $em->persist($form);

            foreach (['fr', 'en'] as $locale) {
                $ft = new FormTranslation();
                $ft->setForm($form)->setLocale($locale)->setTitle($fd[$locale])->setSlug($fd['slug']);
                $em->persist($ft);
            }

            foreach ($fd['fields'] as $pos => $fieldDef) {
                $field = new FormField();
                $field->setForm($form)->setType($fieldDef['type'])->setRequired($fieldDef['req'])->setPosition($pos);
                $em->persist($field);

                foreach (['fr', 'en'] as $locale) {
                    $fft = new FormFieldTranslation();
                    $fft->setField($field)->setLocale($locale)
                        ->setLabel('fr' === $locale ? $fieldDef['fr'] : $fieldDef['en'])
                        ->setPlaceholder('fr' === $locale ? ($fieldDef['ph_fr'] ?: null) : ($fieldDef['ph_en'] ?: null));
                    if (isset($fieldDef['opts_fr']) && 'fr' === $locale) {
                        $fft->setOptions($fieldDef['opts_fr']);
                    }

                    if (isset($fieldDef['opts_en']) && 'en' === $locale) {
                        $fft->setOptions($fieldDef['opts_en']);
                    }

                    $em->persist($fft);
                }
            }

            foreach ($fd['submissions'] as $sub) {
                $fs = new FormSubmission();
                $fs->setForm($form)->setData($sub)->setLocale('fr');
                $em->persist($fs);
            }
        }
    }

    // ── Taxonomies ────────────────────────────────────────────────────────────

    private function createTaxonomies(EntityManagerInterface $em, ?PostType $postType): void
    {
        if (!$postType instanceof PostType) {
            return;
        }

        // Add more terms to the existing "tag" taxonomy
        $tagTaxonomy = $em->getRepository(Taxonomy::class)->findOneBy(['slug' => 'tag']);
        if ($tagTaxonomy instanceof Taxonomy) {
            $tagTerms = ['Symfony', 'Vue.js', 'PHP', 'Tailwind CSS', 'PostgreSQL', 'DevOps', 'Open Source'];
            foreach ($tagTerms as $name) {
                $slug = mb_strtolower(str_replace([' ', '.'], ['-', ''], $name));
                $term = new TaxonomyTerm();
                $term->setTaxonomy($tagTaxonomy);
                foreach (['fr', 'en'] as $locale) {
                    $term->translate($locale)->setName($name)->setSlug($slug);
                }

                $em->persist($term);
            }
        }

        // Add more terms to the existing "category" taxonomy
        $catTaxonomy = $em->getRepository(Taxonomy::class)->findOneBy(['slug' => 'category']);
        if ($catTaxonomy instanceof Taxonomy) {
            $cats = [
                ['fr' => 'Tutoriels', 'en' => 'Tutorials', 'slug' => 'tutoriels'],
                ['fr' => 'Actualités', 'en' => 'News', 'slug' => 'actualites'],
                ['fr' => 'Études de cas', 'en' => 'Case Studies', 'slug' => 'etudes-de-cas'],
                ['fr' => 'Produit', 'en' => 'Product', 'slug' => 'produit'],
            ];
            foreach ($cats as $cat) {
                $term = new TaxonomyTerm();
                $term->setTaxonomy($catTaxonomy);
                $term->translate('fr')->setName($cat['fr'])->setSlug($cat['slug']);
                $term->translate('en')->setName($cat['en'])->setSlug($cat['slug']);
                $em->persist($term);
            }
        }

        // Create a custom "Ressources" taxonomy
        $resTaxonomy = new Taxonomy();
        $resTaxonomy->setSlug('ressource')->setHierarchical(false)->setIsBuiltIn(false);
        $resTaxonomy->translate('fr')->setLabel('Ressource');
        $resTaxonomy->translate('en')->setLabel('Resource');
        $resTaxonomy->getPostTypes()->add($postType);
        $em->persist($resTaxonomy);

        $resTerms = [
            ['fr' => 'Documentation', 'en' => 'Documentation', 'slug' => 'documentation'],
            ['fr' => 'Vidéo',         'en' => 'Video',          'slug' => 'video'],
            ['fr' => 'Webinaire',     'en' => 'Webinar',        'slug' => 'webinar'],
            ['fr' => 'Template',      'en' => 'Template',       'slug' => 'template'],
        ];
        foreach ($resTerms as $rt) {
            $term = new TaxonomyTerm();
            $term->setTaxonomy($resTaxonomy);
            $term->translate('fr')->setName($rt['fr'])->setSlug($rt['slug']);
            $term->translate('en')->setName($rt['en'])->setSlug($rt['slug']);
            $em->persist($term);
        }
    }

    // ── CRM ───────────────────────────────────────────────────────────────────

    /** @return array{Company[], Contact[]} */
    private function createCrm(EntityManagerInterface $em, array $users): array
    {
        $companies = [];
        $companyDefs = [
            ['name' => 'Tech Innovation SARL',     'industry' => 'Informatique & Logiciels',  'website' => 'https://tech-innovation.fr',    'phone' => '+33 1 42 00 11 22', 'address' => '15 rue de la Paix, 75001 Paris'],
            ['name' => 'BioMed France',             'industry' => 'Santé & Biotechnologies',   'website' => 'https://biomed-france.com',     'phone' => '+33 4 91 55 66 77', 'address' => '8 avenue du Prado, 13008 Marseille'],
            ['name' => 'Retail Connect SAS',        'industry' => 'Commerce & Distribution',   'website' => 'https://retail-connect.fr',     'phone' => '+33 4 72 11 33 55', 'address' => '42 cours Gambetta, 69007 Lyon'],
            ['name' => 'Nexus Digital Agency',      'industry' => 'Marketing & Communication', 'website' => 'https://nexus-digital.fr',      'phone' => '+33 1 55 35 00 10', 'address' => '22 rue de Rivoli, 75004 Paris'],
            ['name' => 'Groupe Leclerc Nord',       'industry' => 'Grande Distribution',       'website' => 'https://leclerc-nord.fr',       'phone' => '+33 3 20 44 55 66', 'address' => '1 rue du Commerce, 59000 Lille'],
            ['name' => 'Clinique Saint-Joseph',     'industry' => 'Santé',                     'website' => 'https://clinique-sj.fr',        'phone' => '+33 2 31 06 00 00', 'address' => '2 rue Saint-Ouen, 14000 Caen'],
            ['name' => 'FinTech Horizons SAS',      'industry' => 'Finance & Assurance',       'website' => 'https://fintech-horizons.fr',   'phone' => '+33 1 83 62 10 20', 'address' => '17 rue de la Bourse, 75002 Paris'],
            ['name' => 'EcoBuilding Constructions', 'industry' => 'BTP & Construction',        'website' => 'https://ecobuilding.fr',        'phone' => '+33 4 37 00 33 44', 'address' => '5 allée des Bâtisseurs, 38000 Grenoble'],
            ['name' => 'LogiMove Transport',        'industry' => 'Logistique & Transport',    'website' => 'https://logimove.fr',           'phone' => '+33 5 57 85 00 70', 'address' => 'Zone Portuaire, 33000 Bordeaux'],
            ['name' => 'StartupFactory Lyon',       'industry' => 'Incubateur & Startup',      'website' => 'https://startupfactory.fr',     'phone' => '+33 4 26 68 77 88', 'address' => 'EMLYON, 23 av. Guy de Collongue, 69130 Écully'],
        ];
        foreach ($companyDefs as $def) {
            $c = new Company();
            $c->setName($def['name'])
              ->setIndustry($def['industry'])
              ->setWebsite($def['website'])
              ->setPhone($def['phone'])
              ->setAddress($def['address']);
            $em->persist($c);
            $companies[] = $c;
        }

        $contacts = [];
        $contactDefs = [
            ['first' => 'Pierre',    'last' => 'Dubois',    'email' => 'pierre.dubois@tech-innovation.fr',  'phone' => '+33 6 12 34 56 78', 'company' => 0],
            ['first' => 'Camille',   'last' => 'Leroy',     'email' => 'c.leroy@biomed-france.com',         'phone' => '+33 6 23 45 67 89', 'company' => 1],
            ['first' => 'François',  'last' => 'Moreau',    'email' => 'f.moreau@retail-connect.fr',        'phone' => '+33 6 34 56 78 90', 'company' => 2],
            ['first' => 'Julie',     'last' => 'Chen',      'email' => 'julie.chen@tech-innovation.fr',     'phone' => '+33 6 45 67 89 01', 'company' => 0],
            ['first' => 'Marc',      'last' => 'Fontaine',  'email' => 'marc.fontaine@prospect.com',        'phone' => '+33 6 56 78 90 12', 'company' => null],
            ['first' => 'Isabelle',  'last' => 'Renard',    'email' => 'i.renard@nexus-digital.fr',         'phone' => '+33 6 67 89 01 23', 'company' => 3],
            ['first' => 'David',     'last' => 'Beaumont',  'email' => 'd.beaumont@leclerc-nord.fr',        'phone' => '+33 6 78 90 12 34', 'company' => 4],
            ['first' => 'Nathalie',  'last' => 'Simon',     'email' => 'n.simon@clinique-sj.fr',            'phone' => '+33 6 89 01 23 45', 'company' => 5],
            ['first' => 'Antoine',   'last' => 'Garnier',   'email' => 'a.garnier@fintech-horizons.fr',     'phone' => '+33 6 90 12 34 56', 'company' => 6],
            ['first' => 'Laure',     'last' => 'Michaud',   'email' => 'l.michaud@ecobuilding.fr',          'phone' => '+33 6 01 23 45 67', 'company' => 7],
            ['first' => 'Sébastien', 'last' => 'Blanc',     'email' => 's.blanc@logimove.fr',               'phone' => '+33 6 12 23 34 45', 'company' => 8],
            ['first' => 'Emma',      'last' => 'Rousseau',  'email' => 'e.rousseau@startupfactory.fr',      'phone' => '+33 6 23 34 45 56', 'company' => 9],
            ['first' => 'Thomas',    'last' => 'Lambert',   'email' => 'tlambert@prospect.io',              'phone' => '+33 6 34 45 56 67', 'company' => null],
            ['first' => 'Céline',    'last' => 'Dupuis',    'email' => 'celine.dupuis@startup-prospect.fr', 'phone' => '+33 6 45 56 67 78', 'company' => null],
            ['first' => 'Hugo',      'last' => 'Marchand',  'email' => 'h.marchand@tech-innovation.fr',     'phone' => '+33 6 56 67 78 89', 'company' => 0],
        ];
        foreach ($contactDefs as $def) {
            $c = new Contact();
            $c->setFirstName($def['first'])
              ->setLastName($def['last'])
              ->setEmail($def['email'])
              ->setPhone($def['phone']);
            if (null !== $def['company']) {
                $c->setCompany($companies[$def['company']]);
            }

            $em->persist($c);
            $contacts[] = $c;
        }

        $dealDefs = [
            ['name' => 'Projet CRM Sur-mesure',          'stage' => DealStageEnum::Won,         'value' => '45000.00', 'contact' => 0, 'company' => 0,    'closing' => '-1 month'],
            ['name' => 'Refonte Site Web BioMed',         'stage' => DealStageEnum::Proposal,    'value' => '12000.00', 'contact' => 1, 'company' => 1,    'closing' => '+2 months'],
            ['name' => 'Solution E-commerce',             'stage' => DealStageEnum::Negotiation, 'value' => '28500.00', 'contact' => 2, 'company' => 2,    'closing' => '+3 weeks'],
            ['name' => 'Formation équipe dev',            'stage' => DealStageEnum::Qualified,   'value' => '4800.00',  'contact' => 3, 'company' => 0,    'closing' => '+6 weeks'],
            ['name' => 'Audit infrastructure SI',         'stage' => DealStageEnum::Lead,        'value' => '8000.00',  'contact' => 4, 'company' => null, 'closing' => '+2 months'],
            ['name' => 'Licence Aurora ERP — Tech Inno.', 'stage' => DealStageEnum::Won,         'value' => '99900.00', 'contact' => 0, 'company' => 0,    'closing' => '-2 months'],
            ['name' => 'Migration Cloud BioMed',          'stage' => DealStageEnum::Lost,        'value' => '35000.00', 'contact' => 1, 'company' => 1,    'closing' => '-3 weeks'],
            ['name' => 'Intégration Stripe Retail',       'stage' => DealStageEnum::Qualified,   'value' => '9500.00',  'contact' => 2, 'company' => 2,    'closing' => '+1 month'],
            ['name' => 'Déploiement GED entreprise',      'stage' => DealStageEnum::Proposal,    'value' => '18000.00', 'contact' => 3, 'company' => 0,    'closing' => '+5 weeks'],
            ['name' => 'Support & Maintenance annuel',    'stage' => DealStageEnum::Negotiation, 'value' => '24000.00', 'contact' => 4, 'company' => null, 'closing' => '+3 months'],
        ];
        foreach ($dealDefs as $def) {
            $d = new Deal();
            $d->setName($def['name'])
              ->setStage($def['stage'])
              ->setValue($def['value'])
              ->setContact($contacts[$def['contact']])
              ->setClosingDate(new DateTimeImmutable($def['closing']));
            if (null !== $def['company']) {
                $d->setCompany($companies[$def['company']]);
            }

            $em->persist($d);
        }

        return [$companies, $contacts];
    }

    // ── ERP ───────────────────────────────────────────────────────────────────

    /** @return Product[] */
    private function createErp(EntityManagerInterface $em, array $media): array
    {
        $products = [];
        $defs = [
            ['ref' => 'LIC-CRM-001',  'name' => 'Aurora CRM — Licence annuelle',              'type' => ProductTypeEnum::Digital,   'status' => ProductStatusEnum::Active,   'price' => 79900,   'stock' => null, 'desc' => 'Accès complet au module CRM Aurora. Contacts, entreprises, deals, pipeline Kanban. Licence par utilisateur / an.'],
            ['ref' => 'LIC-ERP-001',  'name' => 'Aurora ERP — Licence annuelle',               'type' => ProductTypeEnum::Digital,   'status' => ProductStatusEnum::Active,   'price' => 99900,   'stock' => null, 'desc' => 'Gestion des produits, stocks, fournisseurs et commandes. Licence par utilisateur / an.'],
            ['ref' => 'LIC-GED-001',  'name' => 'Aurora GED — Licence annuelle',               'type' => ProductTypeEnum::Digital,   'status' => ProductStatusEnum::Active,   'price' => 59900,   'stock' => null, 'desc' => 'Gestion électronique de documents : catégories, statuts, métadonnées, fichiers liés.'],
            ['ref' => 'LIC-PHOTO-001', 'name' => 'Aurora Photo — Licence annuelle',             'type' => ProductTypeEnum::Digital,   'status' => ProductStatusEnum::Active,   'price' => 69900,   'stock' => null, 'desc' => 'Galeries de livraison photo client avec sélections, commentaires et téléchargements sécurisés.'],
            ['ref' => 'LIC-FULL-001', 'name' => 'Aurora Suite Complète — Licence annuelle',    'type' => ProductTypeEnum::Digital,   'status' => ProductStatusEnum::Active,   'price' => 299000,  'stock' => null, 'desc' => 'Tous les modules Aurora inclus : CRM, ERP, E-commerce, Billing, GED, Photo, Editorial.'],
            ['ref' => 'HW-NAS-001',   'name' => 'Serveur NAS 4 baies (8 To)',                  'type' => ProductTypeEnum::Physical,  'status' => ProductStatusEnum::Active,   'price' => 64900,   'stock' => 12,   'desc' => 'Serveur de stockage réseau 4 baies, 8 To (2×4 To RAID 1). Idéal pour la GED et sauvegardes.'],
            ['ref' => 'HW-NAS-002',   'name' => 'Serveur NAS 8 baies Pro (24 To)',              'type' => ProductTypeEnum::Physical,  'status' => ProductStatusEnum::Active,   'price' => 149900,  'stock' => 5,    'desc' => 'Serveur NAS professionnel 8 baies, 24 To RAID 5. Parfait pour les équipes de 20+ personnes.'],
            ['ref' => 'HW-USB-010',   'name' => 'Clé USB sécurisée 256 Go × 10',               'type' => ProductTypeEnum::Physical,  'status' => ProductStatusEnum::Active,   'price' => 14900,   'stock' => 48,   'desc' => 'Pack de 10 clés USB chiffrées AES-256, 256 Go, compatibles USB-C et USB-A.'],
            ['ref' => 'HW-DOCK-001',  'name' => "Station d'accueil USB-C 12 ports",            'type' => ProductTypeEnum::Physical,  'status' => ProductStatusEnum::Active,   'price' => 18900,   'stock' => 24,   'desc' => '12 ports : 3× USB-A, 2× USB-C, HDMI, DisplayPort, Ethernet, SD, audio. Charge 96 W.'],
            ['ref' => 'HW-SCR-001',   'name' => 'Écran 27" 4K IPS — Ergonomie Pro',            'type' => ProductTypeEnum::Physical,  'status' => ProductStatusEnum::Active,   'price' => 44900,   'stock' => 18,   'desc' => 'Moniteur 27 pouces 4K IPS, 120 Hz, Delta E < 2, certifié Pantone. Pied réglable + pivot.'],
            ['ref' => 'SRV-DEV-001',  'name' => 'Formation Développement Web (3 jours)',        'type' => ProductTypeEnum::Service,   'status' => ProductStatusEnum::Active,   'price' => 189000,  'stock' => null, 'desc' => 'Formation intensive 3 jours : Symfony 7, Vue.js 3, Vite. Groupe de 5 à 8 personnes. Intra-entreprise.'],
            ['ref' => 'SRV-AUDIT-001', 'name' => 'Audit & Conseil Sécurité SI (2 jours)',        'type' => ProductTypeEnum::Service,   'status' => ProductStatusEnum::Active,   'price' => 280000,  'stock' => null, 'desc' => 'Audit de sécurité complet : tests de pénétration, analyse des risques, rapport détaillé.'],
            ['ref' => 'SRV-MAINT-001', 'name' => 'Contrat Maintenance Annuel',                   'type' => ProductTypeEnum::Service,   'status' => ProductStatusEnum::Active,   'price' => 120000,  'stock' => null, 'desc' => 'Support niveau 2, mises à jour, sauvegardes supervisées, SLA 4h. Engagement 12 mois.'],
            ['ref' => 'SRV-ONBRD-001', 'name' => 'Onboarding & Déploiement Aurora',              'type' => ProductTypeEnum::Service,   'status' => ProductStatusEnum::Active,   'price' => 350000,  'stock' => null, 'desc' => 'Installation, configuration, migration de données et formation utilisateurs. Forfait clé en main.'],
            ['ref' => 'LIC-CMS-001',  'name' => 'Aurora Editorial CMS — Licence annuelle',      'type' => ProductTypeEnum::Digital,   'status' => ProductStatusEnum::Active,   'price' => 49900,   'stock' => null, 'desc' => 'CMS éditorial complet : articles, taxonomies, formulaires, commentaires, SEO intégré.'],
            ['ref' => 'HW-KBD-001',   'name' => 'Clavier Mécanique sans fil — Compact',        'type' => ProductTypeEnum::Physical,  'status' => ProductStatusEnum::Archived, 'price' => 12900,   'stock' => 0,    'desc' => 'Clavier 75% switches silencieux, autonomie 3 mois, compatible Windows/macOS/Linux. (Archivé)'],
        ];
        foreach ($defs as $i => $def) {
            $p = new Product();
            $p->setName($def['name'])
              ->setReference($def['ref'])
              ->setDescription($def['desc'])
              ->setPriceCents($def['price'])
              ->setCurrency(CurrencyEnum::EUR)
              ->setStatus($def['status'])
              ->setType($def['type']);
            if (null !== $def['stock']) {
                $p->setStockQuantity($def['stock']);
            }

            if (isset($media[$i])) {
                $p->setImage($media[$i]);
            }

            $em->persist($p);
            $products[] = $p;
        }

        return $products;
    }

    // ── Ecommerce ─────────────────────────────────────────────────────────────

    /** @return Listing[] */
    private function createEcommerce(EntityManagerInterface $em, array $products, array $media, array $users): array
    {
        $listings = [];
        $listingDefs = [
            ['product' => 0,  'slug' => 'aurora-crm-licence-annuelle',         'title' => 'Aurora CRM — Licence annuelle',              'desc' => 'Gérez tous vos clients, contacts et deals depuis une interface unifiée. Essai 30 jours inclus.', 'media' => 0],
            ['product' => 1,  'slug' => 'aurora-erp-licence-annuelle',          'title' => 'Aurora ERP — Licence annuelle',               'desc' => 'Gérez vos stocks, fournisseurs et produits avec Aurora ERP. Import/export Excel inclus.', 'media' => 1],
            ['product' => 2,  'slug' => 'aurora-ged-licence-annuelle',          'title' => 'Aurora GED — Gestion Documentaire',          'desc' => 'Centralisez tous vos documents, contrats et ressources internes. Accès par rôle.', 'media' => 0],
            ['product' => 4,  'slug' => 'aurora-suite-complete',                'title' => 'Aurora Suite Complète — Tous Modules',       'desc' => 'L\'offre tout-en-un : CRM, ERP, E-commerce, Billing, GED, Photo et Editorial. Économisez 40%.', 'media' => 1],
            ['product' => 5,  'slug' => 'serveur-nas-4-baies',                  'title' => 'Serveur NAS 4 baies 8 To',                   'desc' => 'La solution de stockage réseau idéale pour les PME. Livraison en 48h, installation incluse.', 'media' => 2],
            ['product' => 6,  'slug' => 'serveur-nas-8-baies-pro-24to',         'title' => 'Serveur NAS 8 baies Pro 24 To',              'desc' => 'Pour les équipes exigeantes : 24 To, RAID 5, interface web d\'administration, backups auto.', 'media' => 3],
            ['product' => 8,  'slug' => 'station-accueil-usb-c-12-ports',       'title' => "Station d'accueil USB-C 12 ports",           'desc' => 'Connectez tout en un port. Compatible MacBook, Dell XPS, Lenovo ThinkPad et plus.', 'media' => 2],
            ['product' => 9,  'slug' => 'ecran-27-pouces-4k-ips',               'title' => 'Écran 27" 4K IPS Ergonomie Pro',             'desc' => 'Couleurs certifiées Pantone, dalle IPS sans flickering, idéal pour designers et devs.', 'media' => 1],
            ['product' => 10, 'slug' => 'formation-developpement-web-3-jours',  'title' => 'Formation Développement Web 3 jours',        'desc' => 'Devenez expert Symfony + Vue.js en 3 jours intensifs. Certification incluse.', 'media' => 3],
            ['product' => 11, 'slug' => 'audit-conseil-securite-si',            'title' => 'Audit & Conseil Sécurité Informatique',      'desc' => 'Nos experts sécurisent votre SI : pentest, analyse de risques, rapport d\'actions correctives.', 'media' => 0],
            ['product' => 12, 'slug' => 'contrat-maintenance-annuel',            'title' => 'Contrat de Maintenance Annuel',               'desc' => 'Tranquillité d\'esprit : support dédié, SLA garanti, mises à jour et sauvegardes supervisées.', 'media' => 2],
            ['product' => 13, 'slug' => 'onboarding-deploiement-aurora',         'title' => 'Pack Onboarding & Déploiement Aurora',       'desc' => 'Démarrez sereinement : installation, configuration, migration et formation. Clé en main.', 'media' => 3],
            ['product' => 14, 'slug' => 'aurora-editorial-cms',                 'title' => 'Aurora Editorial CMS',                       'desc' => 'Publiez facilement articles, pages et formulaires avec un éditeur bloc moderne.', 'media' => 0],
        ];
        foreach ($listingDefs as $def) {
            $l = new Listing();
            $l->setProduct($products[$def['product']])
              ->setSlug($def['slug'])
              ->setMarketingTitle($def['title'])
              ->setMarketingDescription($def['desc'])
              ->setVisibleOnShop(true);
            if (isset($media[$def['media']])) {
                $l->setFeaturedImage($media[$def['media']]);
            }

            $em->persist($l);
            $listings[] = $l;
        }

        // Orders
        [$marie] = $users;
        $orderDefs = [
            [
                'number' => 'ORD-2025-001',
                'token' => bin2hex(random_bytes(16)),
                'email' => 'pierre.dubois@tech-innovation.fr',
                'name' => 'Pierre Dubois',
                'status' => OrderStatusEnum::Paid,
                'total' => 79900,
                'city' => 'Paris',
                'postal' => '75001',
                'country' => 'FR',
                'address' => '15 rue de la Paix',
                'user' => $marie,
                'lines' => [['listing' => 0, 'ref' => 'LIC-CRM-001', 'qty' => 1, 'unit' => 79900]],
            ],
            [
                'number' => 'ORD-2025-002',
                'token' => bin2hex(random_bytes(16)),
                'email' => 'marie.dupont@aurora.app',
                'name' => 'Marie Dupont',
                'status' => OrderStatusEnum::Delivered,
                'total' => 189000,
                'city' => 'Lyon',
                'postal' => '69007',
                'country' => 'FR',
                'address' => '42 cours Gambetta',
                'user' => $marie,
                'lines' => [['listing' => 2, 'ref' => 'SRV-DEV-001', 'qty' => 1, 'unit' => 189000]],
            ],
        ];

        foreach ($orderDefs as $def) {
            $o = new Order();
            $o->setNumber($def['number'])
              ->setToken($def['token'])
              ->setEmail($def['email'])
              ->setName($def['name'])
              ->setStatus($def['status'])
              ->setTotalCents($def['total'])
              ->setCurrency(CurrencyEnum::EUR)
              ->setCity($def['city'])
              ->setPostalCode($def['postal'])
              ->setCountryEnum($def['country'])
              ->setAddressLine1($def['address'])
              ->setCustomer($def['user'])
              ->setLocale('fr');
            $em->persist($o);

            foreach ($def['lines'] as $lineDef) {
                $listing = $listings[$lineDef['listing']];
                $line = new OrderLine();
                $line->setOrder($o)
                     ->setListing($listing)
                     ->setTitleSnapshot($listing->getMarketingTitle() ?? $listing->getProduct()->getName())
                     ->setReferenceSnapshot($lineDef['ref'])
                     ->setQuantity($lineDef['qty'])
                     ->setUnitPriceCents($lineDef['unit'])
                     ->setCurrency(CurrencyEnum::EUR);
                $em->persist($line);
            }
        }

        return $listings;
    }

    // ── Billing ───────────────────────────────────────────────────────────────

    private function createBilling(EntityManagerInterface $em, array $media): void
    {
        $tiersDefs = [
            ['type' => TiersTypeEnum::Supplier,      'name' => 'Dell Technologies France',   'email' => 'business@dell.com',            'phone' => '+33 1 70 37 60 00', 'address' => '1 Rond-Point Benjamin Franklin, 34000 Montpellier'],
            ['type' => TiersTypeEnum::Supplier,      'name' => 'SFR Business',               'email' => 'sfr-business@sfr.fr',          'phone' => '+33 9 70 00 19 19', 'address' => '16 rue du Général Foy, 75008 Paris'],
            ['type' => TiersTypeEnum::Client,        'name' => 'Tech Innovation SARL',       'email' => 'compta@tech-innovation.fr',    'phone' => '+33 1 42 00 11 22', 'address' => '15 rue de la Paix, 75001 Paris'],
            ['type' => TiersTypeEnum::Client,        'name' => 'BioMed France',              'email' => 'comptabilite@biomed-france.com', 'phone' => '+33 4 91 55 66 77', 'address' => '8 avenue du Prado, 13008 Marseille'],
            ['type' => TiersTypeEnum::Client,        'name' => 'Retail Connect SAS',         'email' => 'finance@retail-connect.fr',    'phone' => '+33 4 72 11 33 55', 'address' => '42 cours Gambetta, 69007 Lyon'],
            ['type' => TiersTypeEnum::Supplier,      'name' => 'OVHcloud',                   'email' => 'facturation@ovhcloud.com',     'phone' => '+33 9 72 10 10 07', 'address' => '2 rue Kellermann, 59100 Roubaix'],
            ['type' => TiersTypeEnum::Supplier,      'name' => 'Google Workspace',           'email' => 'billing@google.com',          'phone' => '+33 1 70 36 34 36', 'address' => '8 rue de Londres, 75009 Paris'],
            ['type' => TiersTypeEnum::Supplier,      'name' => 'AWS France',                 'email' => 'aws-billing@amazon.com',      'phone' => '+33 1 85 08 90 90', 'address' => '31 Place des Corolles, 92400 Courbevoie'],
            ['type' => TiersTypeEnum::Partner,       'name' => 'Agence Pixel — Design',      'email' => 'devis@agence-pixel.fr',       'phone' => '+33 1 44 00 55 66', 'address' => '12 rue Oberkampf, 75011 Paris'],
            ['type' => TiersTypeEnum::Partner,       'name' => 'ConseilPro Avocats',         'email' => 'contact@conseilpro.fr',       'phone' => '+33 1 53 04 40 40', 'address' => '10 boulevard Haussmann, 75009 Paris'],
            ['type' => TiersTypeEnum::Subcontractor, 'name' => 'DevStudio — Freelance Dev',  'email' => 'hello@devstudio.io',          'phone' => '+33 6 77 88 99 00', 'address' => 'Remote — 33000 Bordeaux'],
            ['type' => TiersTypeEnum::Subcontractor, 'name' => 'UX Lab — UI Design',        'email' => 'studio@uxlab.fr',             'phone' => '+33 6 11 22 33 44', 'address' => 'Remote — 69000 Lyon'],
            ['type' => TiersTypeEnum::Supplier,      'name' => 'Stripe Inc.',                'email' => 'support@stripe.com',          'phone' => '+1 888 926 2289',   'address' => '354 Oyster Point Blvd, San Francisco CA'],
            ['type' => TiersTypeEnum::Supplier,      'name' => 'GitHub Enterprise',          'email' => 'enterprise@github.com',       'phone' => '+1 877 448 4820',   'address' => '88 Colin P Kelly Jr St, San Francisco CA'],
            ['type' => TiersTypeEnum::Client,        'name' => 'Groupe Leclerc Nord',        'email' => 'achats@leclerc-nord.fr',      'phone' => '+33 3 20 44 55 66', 'address' => '1 rue du Commerce, 59000 Lille'],
            ['type' => TiersTypeEnum::Client,        'name' => 'Clinique Saint-Joseph',      'email' => 'dsi@clinique-sj.fr',         'phone' => '+33 2 31 06 00 00', 'address' => '2 rue Saint-Ouen, 14000 Caen'],
            ['type' => TiersTypeEnum::Partner,       'name' => 'Héber Consulting',           'email' => 'axel@heber-consulting.fr',    'phone' => '+33 6 50 22 33 11', 'address' => 'Remote — 75000 Paris'],
            ['type' => TiersTypeEnum::Supplier,      'name' => 'Adobe Creative Cloud',       'email' => 'billing@adobe.com',          'phone' => '+33 1 85 65 30 30', 'address' => '4 rue de la Victoire, 75009 Paris'],
            ['type' => TiersTypeEnum::Client,        'name' => 'StartupFactory Lyon',        'email' => 'daf@startupfactory.fr',      'phone' => '+33 4 26 68 77 88', 'address' => 'EMLYON, 23 avenue Guy de Collongue, 69130 Écully'],
            ['type' => TiersTypeEnum::Subcontractor, 'name' => 'DataInsight Analytics',      'email' => 'data@datainsight.fr',        'phone' => '+33 6 44 55 66 77', 'address' => 'Remote — 31000 Toulouse'],
        ];
        $tiers = [];
        foreach ($tiersDefs as $def) {
            $t = new Tiers();
            $t->setType($def['type'])
              ->setName($def['name'])
              ->setEmail($def['email'])
              ->setPhone($def['phone'])
              ->setAddress($def['address']);
            $em->persist($t);
            $tiers[] = $t;
        }

        // One validated invoice from Dell
        $inv = new Invoice();
        $inv->setTiers($tiers[0])
            ->setStatus(InvoiceStatusEnum::Validated)
            ->setIssuedAt(new DateTimeImmutable('-15 days'))
            ->setDueAt(new DateTimeImmutable('+15 days'))
            ->setSubtotalCents(64900)
            ->setTotalNetCents(64900)
            ->setTotalVatCents(12980)
            ->setTotalGrossCents(77880)
            ->setCurrency(CurrencyEnum::EUR)
            ->setProject('Acquisition NAS Serveur')
            ->setPaymentTerms('30 jours net');

        if (isset($media[5])) {
            $inv->setDocument($media[5]);
        }

        $em->persist($inv);

        $line = new InvoiceLine();
        $line->setInvoice($inv)
             ->setLabel('Serveur NAS 4 baies 8 To — Dell PowerStore 500T')
             ->setQuantity('1')
             ->setUnitPriceCents(64900)
             ->setVatRateBp(2000)
             ->setTotalNetCents(64900)
             ->setTotalGrossCents(77880);
        $em->persist($line);

        // One draft invoice from SFR
        $inv2 = new Invoice();
        $inv2->setTiers($tiers[1])
             ->setStatus(InvoiceStatusEnum::Draft)
             ->setIssuedAt(new DateTimeImmutable('-3 days'))
             ->setSubtotalCents(8990)
             ->setTotalNetCents(8990)
             ->setTotalVatCents(1798)
             ->setTotalGrossCents(10788)
             ->setCurrency(CurrencyEnum::EUR)
             ->setProject('Téléphonie & Internet')
             ->setPaymentTerms('À réception');
        $em->persist($inv2);

        $line2 = new InvoiceLine();
        $line2->setInvoice($inv2)
              ->setLabel('Abonnement SFR Pro Fibre 1 Gb/s — Novembre 2025')
              ->setQuantity('1')
              ->setUnitPriceCents(8990)
              ->setVatRateBp(2000)
              ->setTotalNetCents(8990)
              ->setTotalGrossCents(10788);
        $em->persist($line2);

        // Additional invoices for variety
        $extraInvoices = [
            ['ti' => 5, 'status' => InvoiceStatusEnum::Paid,       'label' => 'Hébergement OVHcloud — Serveur dédié 3 mois', 'net' => 44700,  'gross' => 53640, 'project' => 'Infrastructure Prod',   'ago' => '-2 months', 'terms' => '30 jours net'],
            ['ti' => 6, 'status' => InvoiceStatusEnum::Paid,       'label' => 'Google Workspace Business Plus — 10 licences', 'net' => 13200,  'gross' => 15840, 'project' => 'Outils bureautique',    'ago' => '-45 days',  'terms' => 'Mensuel'],
            ['ti' => 7, 'status' => InvoiceStatusEnum::Validated,  'label' => 'AWS EC2 + RDS — Octobre 2025',                 'net' => 28600,  'gross' => 34320, 'project' => 'Cloud Aurora Tech',     'ago' => '-10 days',  'terms' => 'À réception'],
            ['ti' => 8, 'status' => InvoiceStatusEnum::NeedsReview, 'label' => 'Prestation design UI — Refonte charte Q4',      'net' => 18000,  'gross' => 21600, 'project' => 'Refonte Marque 2025',   'ago' => '-5 days',   'terms' => '30 jours fin de mois'],
            ['ti' => 9, 'status' => InvoiceStatusEnum::Draft,      'label' => 'Dev front-end Aurora — Sprint 8',               'net' => 9600,   'gross' => 11520, 'project' => 'Aurora v2.1',            'ago' => '-2 days',   'terms' => '15 jours'],
            ['ti' => 10, 'status' => InvoiceStatusEnum::Paid,       'label' => 'Stripe — Commission transactions Octobre 2025', 'net' => 4200,   'gross' => 5040,  'project' => 'E-commerce',             'ago' => '-30 days',  'terms' => 'Mensuel'],
            ['ti' => 11, 'status' => InvoiceStatusEnum::Validated,  'label' => 'Adobe CC — 5 licences annuelles',               'net' => 31500,  'gross' => 37800, 'project' => 'Studio créatif',         'ago' => '-7 days',   'terms' => 'Annuel'],
            ['ti' => 12, 'status' => InvoiceStatusEnum::Draft,      'label' => 'Analyse données — Dashboard Q3 2025',           'net' => 7200,   'gross' => 8640,  'project' => 'BI & Analytics',         'ago' => '-1 day',    'terms' => '30 jours net'],
        ];
        foreach ($extraInvoices as $ei) {
            $inv = new Invoice();
            $inv->setTiers($tiers[$ei['ti']] ?? $tiers[0])
                ->setStatus($ei['status'])
                ->setIssuedAt(new DateTimeImmutable($ei['ago']))
                ->setDueAt(new DateTimeImmutable($ei['ago'].' +30 days'))
                ->setSubtotalCents($ei['net'])
                ->setTotalNetCents($ei['net'])
                ->setTotalVatCents((int) ($ei['net'] * 0.2))
                ->setTotalGrossCents($ei['gross'])
                ->setCurrency(CurrencyEnum::EUR)
                ->setProject($ei['project'])
                ->setPaymentTerms($ei['terms']);
            $em->persist($inv);

            $line = new InvoiceLine();
            $line->setInvoice($inv)->setLabel($ei['label'])->setQuantity('1')
                 ->setUnitPriceCents($ei['net'])->setVatRateBp(2000)
                 ->setTotalNetCents($ei['net'])->setTotalGrossCents($ei['gross']);
            $em->persist($line);
        }
    }

    // ── Photo ─────────────────────────────────────────────────────────────────

    private function createPhoto(EntityManagerInterface $em, array $media, array $users, array $contacts): void
    {
        [$marie] = $users;

        // Gallery 1 — Portfolio (with visitor picks + comments)
        $g1 = new Gallery();
        $g1->setTitle('Portfolio Projets Aurora Tech 2025')
           ->setSlug('portfolio-aurora-tech-2025')
           ->setDescription('Sélection de visuels réalisés pour nos projets clients en 2025. Galerie privée — accès sur invitation.')
           ->setCreatedBy($marie)
           ->setAllowOriginals(true)
           ->setAllowZipDownload(true)
           ->setAllowVisitorComments(true)
           ->setMaxPicks(5);

        if (isset($media[0])) {
            $g1->setCoverMedia($media[0]);
        }

        $em->persist($g1);

        $imageMedia = array_values(array_filter(array_slice($media, 0, 5), fn ($m): bool => str_contains((string) $m->getMimeType(), 'image')));
        $items1 = [];
        foreach ($imageMedia as $i => $m) {
            $item = new GalleryItem();
            $item->setGallery($g1)->setMedia($m)->setNumber($i + 1);
            $em->persist($item);
            $items1[] = $item;
        }

        // Visitor comments on gallery 1
        $commentTexts = [
            'Superbes photos ! Le rendu est vraiment professionnel.',
            'J\'adore la 3ème photo, les couleurs sont magnifiques.',
            'Peut-on télécharger les originaux ? Merci !',
        ];
        foreach ($commentTexts as $ci => $text) {
            if (!isset($items1[$ci % count($items1)])) {
                continue;
            }

            $c = new GalleryItemComment();
            $c->setGalleryItem($items1[$ci % count($items1)])
              ->setContent($text)
              ->setVisitorToken(bin2hex(random_bytes(8)))
              ->setVisitorName('Visiteur '.($ci + 1));
            $em->persist($c);
        }

        // Visitor picks on gallery 1
        $visitors = [
            ['token' => bin2hex(random_bytes(8)), 'name' => 'Pierre Dubois',  'email' => 'pierre.dubois@tech-innovation.fr'],
            ['token' => bin2hex(random_bytes(8)), 'name' => 'Camille Leroy',  'email' => 'c.leroy@biomed-france.com'],
        ];
        foreach ($visitors as $vi => $visitor) {
            foreach (array_slice($items1, 0, 2) as $item) {
                $pick = new GalleryPick();
                $pick->setGalleryItem($item)
                     ->setVisitorToken($visitor['token'])
                     ->setVisitorName($visitor['name'])
                     ->setVisitorEmail($visitor['email']);
                $em->persist($pick);
            }

            // Finalization for first visitor
            if (0 === $vi) {
                $fin = new GalleryFinalization();
                $fin->setGallery($g1)
                    ->setVisitorToken($visitor['token'])
                    ->setVisitorName($visitor['name'])
                    ->setVisitorEmail($visitor['email']);
                $em->persist($fin);
            }
        }

        // Gallery 2 — Mariage Dupont (simple, no interactions yet)
        $g2 = new Gallery();
        $g2->setTitle('Mariage Dupont — Juin 2025')
           ->setSlug('mariage-dupont-juin-2025')
           ->setDescription('Livraison photos du mariage de Pierre & Julie Dupont, 14 juin 2025.')
           ->setCreatedBy($marie)
           ->setAllowOriginals(true)
           ->setAllowZipDownload(true)
           ->setAllowVisitorComments(true)
           ->setMaxPicks(30);

        if (isset($media[2])) {
            $g2->setCoverMedia($media[2]);
        }

        $em->persist($g2);

        foreach ($imageMedia as $i => $m) {
            $item = new GalleryItem();
            $item->setGallery($g2)->setMedia($m)->setNumber($i + 1);
            $em->persist($item);
        }

        // Gallery 3 — Conférence Aurora Tech Day
        $g3 = new Gallery();
        $g3->setTitle('Aurora Tech Day 2025 — Photos')
           ->setSlug('aurora-tech-day-2025-photos')
           ->setDescription("Galerie des photos officielles de l'Aurora Tech Day du 15 mai 2025.")
           ->setCreatedBy($marie)
           ->setAllowOriginals(false)
           ->setAllowZipDownload(false)
           ->setAllowVisitorComments(false);

        if (isset($media[1])) {
            $g3->setCoverMedia($media[1]);
        }

        $em->persist($g3);

        foreach ($imageMedia as $i => $m) {
            $item = new GalleryItem();
            $item->setGallery($g3)->setMedia($m)->setNumber($i + 1);
            $em->persist($item);
        }
    }

    // ── GED ───────────────────────────────────────────────────────────────────

    private function createGed(EntityManagerInterface $em, array $media): void
    {
        $catDefs = [
            ['name' => 'Contrats Clients',         'slug' => 'contrats-clients',       'desc' => 'Contrats signés avec nos clients et partenaires commerciaux.'],
            ['name' => 'Documentation Technique',  'slug' => 'doc-technique',          'desc' => 'Guides d\'installation, spécifications et manuels techniques.'],
            ['name' => 'Ressources Marketing',     'slug' => 'ressources-marketing',   'desc' => 'Visuels, présentations et supports de communication.'],
            ['name' => 'Ressources Humaines',      'slug' => 'ressources-humaines',    'desc' => 'Fiches de poste, procédures RH et documents administratifs du personnel.'],
            ['name' => 'Finance & Comptabilité',   'slug' => 'finance-comptabilite',   'desc' => 'Rapports financiers, budgets et documents comptables.'],
            ['name' => 'Qualité & Conformité',     'slug' => 'qualite-conformite',     'desc' => 'Politiques qualité, audits et certifications.'],
        ];
        $categories = [];
        foreach ($catDefs as $def) {
            $c = new DocumentCategory();
            $c->setName($def['name'])->setSlug($def['slug'])->setDescription($def['desc']);
            $em->persist($c);
            $categories[] = $c;
        }

        $docDefs = [
            ['title' => 'Contrat Tech Innovation SARL 2025',           'cat' => 0, 'status' => DocumentStatusEnum::Published, 'desc' => 'Contrat de prestation de services signé le 15 janvier 2025. Durée : 12 mois renouvelable.', 'file' => 5],
            ['title' => 'Contrat BioMed France — Maintenance 2025',    'cat' => 0, 'status' => DocumentStatusEnum::Published, 'desc' => 'Contrat de maintenance et support niveau 2 pour la suite Aurora.', 'file' => null],
            ['title' => 'Avenant Contrat Retail Connect — Jan 2025',   'cat' => 0, 'status' => DocumentStatusEnum::Draft,     'desc' => 'Avenant tarifaire en cours de négociation pour le renouvellement 2025.', 'file' => null],
            ['title' => "Guide d'installation Aurora v2.0",            'cat' => 1, 'status' => DocumentStatusEnum::Published, 'desc' => 'Documentation complète pour installer et configurer Aurora en production.', 'file' => null],
            ['title' => 'API Aurora — Documentation Développeur v2.1', 'cat' => 1, 'status' => DocumentStatusEnum::Published, 'desc' => 'Référence complète de l\'API REST Aurora : endpoints, authentification, exemples.', 'file' => null],
            ['title' => 'Architecture Technique Aurora — Whitepaper',  'cat' => 1, 'status' => DocumentStatusEnum::Archived,  'desc' => 'Document d\'architecture technique v1.x (archivé, remplacé par la version 2.x).', 'file' => null],
            ['title' => 'Rapport Annuel 2024 — Aurora Tech',           'cat' => 4, 'status' => DocumentStatusEnum::Draft,     'desc' => 'Bilan financier et opérationnel de l\'exercice 2024. En cours de validation.', 'file' => null],
            ['title' => 'Budget Prévisionnel 2025 — Aurora Tech',      'cat' => 4, 'status' => DocumentStatusEnum::Published, 'desc' => 'Budget prévisionnel approuvé par le comité de direction le 10 janvier 2025.', 'file' => null],
            ['title' => 'Charte Graphique Aurora — Brand Guidelines',  'cat' => 2, 'status' => DocumentStatusEnum::Published, 'desc' => 'Couleurs, typographies, logos et règles d\'utilisation de la marque Aurora.', 'file' => 0],
            ['title' => 'Kit Presse Aurora Tech Day 2025',             'cat' => 2, 'status' => DocumentStatusEnum::Published, 'desc' => 'Communiqué de presse, visuels HD et biographies intervenants.', 'file' => null],
            ['title' => 'Fiche de Poste — Développeur Full Stack',     'cat' => 3, 'status' => DocumentStatusEnum::Published, 'desc' => 'Description du poste, compétences requises et processus de recrutement.', 'file' => null],
            ['title' => 'Politique de Télétravail — Aurora Tech',      'cat' => 3, 'status' => DocumentStatusEnum::Published, 'desc' => 'Règles et procédures applicables au travail à distance.', 'file' => null],
            ['title' => 'Certification ISO 27001 — Audit 2024',        'cat' => 5, 'status' => DocumentStatusEnum::Published, 'desc' => 'Rapport d\'audit de conformité ISO 27001 réalisé en novembre 2024.', 'file' => null],
        ];
        foreach ($docDefs as $def) {
            $d = new Document();
            $d->setTitle($def['title'])
              ->setDescription($def['desc'])
              ->setStatus($def['status'])
              ->setCategory($categories[$def['cat']]);
            if (null !== $def['file'] && isset($media[$def['file']])) {
                $d->setFile($media[$def['file']]);
            }

            $em->persist($d);
        }
    }

    // ── Menus ─────────────────────────────────────────────────────────────────

    private function createMenuItems(EntityManagerInterface $em): void
    {
        $primary = $em->getRepository(Menu::class)->findOneBy(['location' => 'primary']);
        $footer = $em->getRepository(Menu::class)->findOneBy(['location' => 'footer']);

        if ($primary instanceof Menu) {
            $primaryItems = [
                ['fr' => 'Accueil',          'en' => 'Home',           'url' => '/',                  'type' => MenuItemTargetTypeEnum::Home],
                ['fr' => 'Notre histoire',   'en' => 'Our story',      'url' => '/notre-histoire',    'type' => MenuItemTargetTypeEnum::CustomUrl],
                ['fr' => 'Solutions',        'en' => 'Solutions',      'url' => '/solutions',         'type' => MenuItemTargetTypeEnum::CustomUrl],
                ['fr' => 'Blog',             'en' => 'Blog',           'url' => '/blog',              'type' => MenuItemTargetTypeEnum::PostTypeArchive],
                ['fr' => 'Tarifs',           'en' => 'Pricing',        'url' => '/tarifs',            'type' => MenuItemTargetTypeEnum::CustomUrl],
                ['fr' => 'Boutique',         'en' => 'Shop',           'url' => '/shop',              'type' => MenuItemTargetTypeEnum::CustomUrl],
                ['fr' => 'Ressources',       'en' => 'Resources',      'url' => '/ressources',        'type' => MenuItemTargetTypeEnum::CustomUrl],
                ['fr' => 'À propos',         'en' => 'About',          'url' => '/a-propos',          'type' => MenuItemTargetTypeEnum::CustomUrl],
                ['fr' => 'Contact',          'en' => 'Contact',        'url' => '/contact',           'type' => MenuItemTargetTypeEnum::CustomUrl],
            ];
            foreach ($primaryItems as $pos => $def) {
                $item = new MenuItem();
                $item->setMenu($primary)
                     ->setTargetType($def['type'])
                     ->setCustomUrl($def['url'])
                     ->setPosition($pos);
                $em->persist($item);
                foreach (['fr', 'en'] as $locale) {
                    $tr = new MenuItemTranslation();
                    $tr->setMenuItem($item)->setLocale($locale)->setLabel('fr' === $locale ? $def['fr'] : $def['en']);
                    $em->persist($tr);
                }
            }
        }

        if ($footer instanceof Menu) {
            $sections = [
                ['fr' => 'Produit',     'en' => 'Product',      'url' => null, 'children' => [
                    ['fr' => 'Fonctionnalités',  'en' => 'Features',    'url' => '/fonctionnalites'],
                    ['fr' => 'Tarifs',           'en' => 'Pricing',     'url' => '/tarifs'],
                    ['fr' => 'Roadmap',          'en' => 'Roadmap',     'url' => '/roadmap'],
                    ['fr' => 'Nouveautés',       'en' => 'Changelog',   'url' => '/changelog'],
                ]],
                ['fr' => 'Ressources',  'en' => 'Resources',    'url' => null, 'children' => [
                    ['fr' => 'Documentation',   'en' => 'Documentation', 'url' => '/docs'],
                    ['fr' => 'Blog',            'en' => 'Blog',         'url' => '/blog'],
                    ['fr' => 'Tutoriels',       'en' => 'Tutorials',    'url' => '/tutoriels'],
                    ['fr' => 'Status',          'en' => 'Status',       'url' => 'https://status.aurora.app'],
                ]],
                ['fr' => 'Entreprise',  'en' => 'Company',      'url' => null, 'children' => [
                    ['fr' => 'À propos',        'en' => 'About',        'url' => '/a-propos'],
                    ['fr' => 'Équipe',          'en' => 'Team',         'url' => '/equipe'],
                    ['fr' => 'Carrières',       'en' => 'Careers',      'url' => '/carrieres'],
                    ['fr' => 'Presse',          'en' => 'Press',        'url' => '/presse'],
                ]],
                ['fr' => 'Légal',       'en' => 'Legal',        'url' => null, 'children' => [
                    ['fr' => 'CGU',             'en' => 'Terms',        'url' => '/cgu'],
                    ['fr' => 'Confidentialité', 'en' => 'Privacy',      'url' => '/confidentialite'],
                    ['fr' => 'Cookies',         'en' => 'Cookies',      'url' => '/cookies'],
                    ['fr' => 'Contact',         'en' => 'Contact',      'url' => '/contact'],
                ]],
            ];

            $pos = 0;
            foreach ($sections as $section) {
                $parent = new MenuItem();
                $parent->setMenu($footer)
                       ->setTargetType(MenuItemTargetTypeEnum::CustomUrl)
                       ->setCustomUrl($section['url'])
                       ->setPosition($pos++);
                $em->persist($parent);
                foreach (['fr', 'en'] as $locale) {
                    $tr = new MenuItemTranslation();
                    $tr->setMenuItem($parent)->setLocale($locale)->setLabel('fr' === $locale ? $section['fr'] : $section['en']);
                    $em->persist($tr);
                }

                foreach ($section['children'] as $childPos => $child) {
                    $item = new MenuItem();
                    $item->setMenu($footer)
                         ->setParent($parent)
                         ->setTargetType(MenuItemTargetTypeEnum::CustomUrl)
                         ->setCustomUrl($child['url'])
                         ->setPosition($childPos);
                    $em->persist($item);
                    foreach (['fr', 'en'] as $locale) {
                        $tr = new MenuItemTranslation();
                        $tr->setMenuItem($item)->setLocale($locale)->setLabel('fr' === $locale ? $child['fr'] : $child['en']);
                        $em->persist($tr);
                    }
                }
            }
        }
    }
}
