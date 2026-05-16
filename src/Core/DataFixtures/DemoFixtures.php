<?php

declare(strict_types=1);

namespace Aurora\Core\DataFixtures;

use Aurora\Core\Agency\Entity\Agency;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Audit\Entity\AbstractAuditLog;
use Aurora\Core\Audit\Entity\AuditLog;
use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Menu\Entity\Menu;
use Aurora\Core\Menu\Entity\MenuItem;
use Aurora\Core\Menu\Entity\MenuItemTranslation;
use Aurora\Core\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Core\Service\Entity\Service;
use Aurora\Core\Service\Entity\ServiceInterface;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Service\SettingsService;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Enum\ContactSourceEnum;
use Aurora\Module\Crm\ContactTag\Entity\ContactTag;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategory;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryTranslation;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTag;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagTranslation;
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
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategory;
use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolder;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTag;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Aurora\Module\Hr\Employee\Entity\Employee;
use Aurora\Module\Notes\Markdown\Entity\AbstractMarkdownNote;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNote;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNoteInterface;
use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryFinalization;
use Aurora\Module\Photo\Gallery\Entity\GalleryItem;
use Aurora\Module\Photo\Gallery\Entity\GalleryItemComment;
use Aurora\Module\Photo\Gallery\Entity\GalleryPick;
use Aurora\Module\Planning\Event\Entity\PlanningEvent;
use Aurora\Module\Planning\Event\Enum\PlanningEventStatusEnum;
use Aurora\Module\Planning\Planning\Entity\Planning;
use Aurora\Module\Planning\Planning\Enum\PlanningVisibilityEnum;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectLabel;
use Aurora\Module\Project\Entity\ProjectSprint;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Aurora\Core\Media\Service\MediaUrlGenerator;

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
        private readonly SettingsService $settingsManager,
        #[Autowire('%app.upload_dir%')]
        private readonly string $uploadDir,
        private readonly Filesystem $fs = new Filesystem(),
        protected readonly MediaUrlGenerator $mediaUrlGenerator,
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
        $this->createAgenciesAndServices($manager, $users);
        $media = $this->createMedia($manager);
        $postType = $manager->getRepository(PostType::class)->findOneBy(['slug' => 'article']);

        $terms = $this->createTaxonomies($manager, $postType);
        $posts = $this->createEditorial($manager, $postType, $media, $users, $terms);
        $this->createComments($manager, $posts);
        $this->createForms($manager);
        [$companies, $contacts] = $this->createCrm($manager, $users);
        $products = $this->createErp($manager, $media);
        $listings = $this->createEcommerce($manager, $products, $media, $users);
        $this->createListingCategories($manager, $listings);
        $this->createListingTags($manager, $listings);
        $this->createBilling($manager, $media);
        $this->createPhoto($manager, $media, $users, $contacts);
        $this->createGed($manager, $media);
        $this->createPdfForm($manager);
        $this->createHr($manager, $users);
        $this->createPlanning($manager, $users);
        $this->createProjects($manager, $users, $companies, $contacts);
        $this->createMarkdownNotes($manager, $users);
        $this->createMenuItems($manager, $media);

        $manager->flush();

        // Assign favicon after flush so IDs are available.
        // Use the landscape image (media[1]) as both favicon and logo.
        if (isset($media[1]) && null !== $media[1]->getId()) {
            $faviconId = (string) $media[1]->getId();
            $this->settingsManager->set(ApplicationParameterEnum::FaviconMediaId->value, $faviconId);
            $this->settingsManager->set(ApplicationParameterEnum::LogoMediaId->value, $faviconId);
        }
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
                'privileges' => [
                    'general.dashboard.view',
                    // CRM — full sales access
                    'crm.contacts.view', 'crm.contacts.create', 'crm.contacts.edit', 'crm.contacts.delete',
                    'crm.companies.view', 'crm.companies.create', 'crm.companies.edit', 'crm.companies.delete',
                    'crm.deals.view', 'crm.deals.create', 'crm.deals.edit', 'crm.deals.delete',
                    // GED — full document management
                    'ged.documents.view', 'ged.documents.create', 'ged.documents.edit', 'ged.documents.delete',
                    'ged.categories.view', 'ged.categories.create', 'ged.categories.edit', 'ged.categories.delete',
                    'ged.tags.manage', 'ged.folders.manage',
                ],
                'mood' => 'Commercial senior',
            ],
            [
                'email' => 'sophie.bernard@aurora.app',
                'name' => 'Sophie Bernard',
                'role' => UserRoleEnum::User,
                'privileges' => [
                    'general.dashboard.view',
                    // Editorial — full editorial workflow
                    'editorial.posts.view', 'editorial.posts.create', 'editorial.posts.edit', 'editorial.posts.delete',
                    'editorial.menus.view', 'editorial.menus.create', 'editorial.menus.edit', 'editorial.menus.delete',
                    'editorial.taxonomies.view', 'editorial.taxonomies.create', 'editorial.taxonomies.edit',
                    'editorial.post_types.view',
                    'editorial.comments.view', 'editorial.comments.moderate', 'editorial.comments.delete',
                    'editorial.forms.view', 'editorial.forms.create', 'editorial.forms.edit', 'editorial.forms.delete',
                    'editorial.sitemap.view', 'editorial.sitemap.regenerate',
                    // Media library (editors need full CRUD on items + folders)
                    'media.view', 'media.create', 'media.edit', 'media.delete',
                    'media.folders.create', 'media.folders.edit', 'media.folders.delete',
                ],
                'mood' => 'Rédactrice en chef ✍️',
            ],
            [
                'email' => 'thomas.petit@aurora.app',
                'name' => 'Thomas Petit',
                'role' => UserRoleEnum::User,
                'privileges' => [
                    'general.dashboard.view',
                    // Ecommerce — full sales access (refund stays admin-only)
                    'ecommerce.listings.view', 'ecommerce.listings.create', 'ecommerce.listings.edit', 'ecommerce.listings.delete',
                    'ecommerce.orders.view', 'ecommerce.orders.edit',
                    // Billing — full accounting
                    'billing.invoices.view', 'billing.invoices.create', 'billing.invoices.edit', 'billing.invoices.delete',
                    'billing.tiers.view', 'billing.tiers.edit', 'billing.tiers.delete',
                    'billing.ocr.import',
                    // ERP products
                    'erp.products.view', 'erp.products.create', 'erp.products.edit',
                ],
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

    // ── Agencies & Services ───────────────────────────────────────────────────

    /** @param User[] $users */
    private function createAgenciesAndServices(EntityManagerInterface $em, array $users): void
    {
        $agencyDefs = [
            'Agence Nord',
            'Agence Sud',
            'Agence Est',
            'Agence Ouest',
            'Siège Social',
        ];

        $serviceDefs = [
            'Développement',
            'Commercial',
            'Ressources Humaines',
            'Direction',
            'Marketing',
        ];

        // Resolve concrete classes via Doctrine metadata so clients that
        // substitute Agency/Service through resolve_target_entities still
        // get the right class — `new Agency()` would bypass the mapping.
        /** @var class-string<Agency> $agencyClass */
        $agencyClass = $em->getClassMetadata(AgencyInterface::class)->getName();
        /** @var class-string<Service> $serviceClass */
        $serviceClass = $em->getClassMetadata(ServiceInterface::class)->getName();

        $agencies = [];
        foreach ($agencyDefs as $name) {
            $agency = new $agencyClass()->setName($name);
            $em->persist($agency);
            $agencies[] = $agency;
        }

        $services = [];
        foreach ($serviceDefs as $name) {
            $service = new $serviceClass()->setName($name);
            $em->persist($service);
            $services[] = $service;
        }

        $em->flush();

        foreach ($users as $index => $user) {
            $user->setAgency($agencies[$index % count($agencies)]);
            $user->setService($services[$index % count($services)]);
        }
    }

    /** @return Media[] */
    private function createMedia(EntityManagerInterface $em): array
    {
        $month = new DateTimeImmutable()->format('Y/m');
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

    /**
     * @param array<string, TaxonomyTermInterface> $terms
     *
     * @return Post[]
     */
    private function createEditorial(EntityManagerInterface $em, ?PostType $postType, array $media, array $users, array $terms = []): array
    {
        if (!$postType instanceof PostType) {
            return [];
        }

        $createdPosts = [];

        $u0 = isset($media[0]) ? $this->mediaUrlGenerator->publicUrl($media[0]).'?v=0' : '';
        $u1 = isset($media[1]) ? $this->mediaUrlGenerator->publicUrl($media[1]).'?v=0' : '';
        $u2 = isset($media[2]) ? $this->mediaUrlGenerator->publicUrl($media[2]).'?v=0' : '';
        $u3 = isset($media[3]) ? $this->mediaUrlGenerator->publicUrl($media[3]).'?v=0' : '';

        $tag = static function (Post $post, array $slugs, array $allTerms): void {
            foreach ($slugs as $slug) {
                if (isset($allTerms[$slug])) {
                    $post->addTerm($allTerms[$slug]);
                }
            }
        };

        /**
         * Bilingual posts (fr + en).
         * Each entry has: fr{title,slug,excerpt,blocks}, en{…}, media, terms[].
         */
        $bilingualDefs = [
            [
                'fr' => [
                    'title' => 'Bienvenue sur Aurora — La suite métier tout-en-un',
                    'slug' => 'bienvenue-sur-aurora',
                    'excerpt' => 'Découvrez Aurora, la plateforme qui unifie CRM, ERP, e-commerce, facturation et gestion documentaire.',
                    'blocks' => [
                        ['type' => 'header',    'data' => ['text' => 'Une plateforme pour tout gérer', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => "Aurora unifie CRM, ERP, e-commerce, facturation, GED et photographie dans un seul espace d'administration. ".self::LOREM]],
                        ['type' => 'image',     'data' => ['file' => ['url' => $u0, 'width' => 1280, 'height' => 853], 'caption' => "L'interface Aurora — tableau de bord principal", 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                        ['type' => 'header',    'data' => ['text' => 'CRM & Gestion commerciale', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Gérez vos contacts, entreprises et opportunités depuis une interface unifiée. Suivez chaque deal de la prospection à la signature. '.self::LOREM]],
                        ['type' => 'header',    'data' => ['text' => 'E-commerce intégré', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Publiez votre catalogue, gérez les commandes et les paiements Stripe sans quitter votre espace admin. '.self::LOREM]],
                        ['type' => 'image',     'data' => ['file' => ['url' => $u1, 'width' => 1280, 'height' => 720], 'caption' => 'Module e-commerce Aurora — gestion des listings', 'withBorder' => false, 'withBackground' => false, 'stretched' => false]],
                        ['type' => 'header',    'data' => ['text' => 'GED & Facturation', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ],
                ],
                'en' => [
                    'title' => 'Welcome to Aurora — The All-in-One Business Suite',
                    'slug' => 'welcome-to-aurora',
                    'excerpt' => 'Discover Aurora, the platform that unifies CRM, ERP, e-commerce, billing and document management.',
                    'blocks' => [
                        ['type' => 'header',    'data' => ['text' => 'One platform to manage everything', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                        ['type' => 'image',     'data' => ['file' => ['url' => $u0, 'width' => 1280, 'height' => 853], 'caption' => 'Aurora admin dashboard', 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ],
                ],
                'media' => $media[0] ?? null,
                'terms' => ['actualites', 'nouveaute', 'open-source'],
                'ago' => '3 weeks',
            ],
            [
                'fr' => [
                    'title' => 'Les meilleures pratiques du développement web en 2025',
                    'slug' => 'meilleures-pratiques-developpement-web-2025',
                    'excerpt' => 'Symfony, Vue.js, Vite, Tailwind CSS — le stack moderne pour construire des applications web performantes.',
                    'blocks' => [
                        ['type' => 'header',    'data' => ['text' => 'Le stack moderne en 2025', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => "Le développement web évolue vite. En 2025, les meilleures équipes s'appuient sur des outils modernes, typés et performants. ".self::LOREM]],
                        ['type' => 'image',     'data' => ['file' => ['url' => $u1, 'width' => 1280, 'height' => 720], 'caption' => "Architecture d'une application Aurora moderne", 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                        ['type' => 'header',    'data' => ['text' => 'Symfony 7 & PHP 8.4', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Les attributs PHP 8.4, les readonly properties et les énumérations font de PHP un langage moderne et expressif. '.self::LOREM]],
                        ['type' => 'header',    'data' => ['text' => 'Vue.js 3 & Composition API', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                        ['type' => 'image',     'data' => ['file' => ['url' => $u2, 'width' => 800, 'height' => 1000], 'caption' => 'Développement front-end avec Vue.js 3', 'withBorder' => true, 'withBackground' => false, 'stretched' => false]],
                        ['type' => 'header',    'data' => ['text' => 'Tailwind CSS v4', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Le utility-first CSS framework repensé avec une configuration CSS-native et des performances de build imbattables. '.self::LOREM]],
                    ],
                ],
                'en' => [
                    'title' => 'Web Development Best Practices in 2025',
                    'slug' => 'web-development-best-practices-2025',
                    'excerpt' => 'Symfony, Vue.js, Vite, Tailwind CSS — the modern stack for performant web applications.',
                    'blocks' => [
                        ['type' => 'header',    'data' => ['text' => 'The modern stack in 2025', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                        ['type' => 'image',     'data' => ['file' => ['url' => $u1, 'width' => 1280, 'height' => 720], 'caption' => 'Modern web development architecture', 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ],
                ],
                'media' => $media[1] ?? null,
                'terms' => ['tutoriels', 'symfony', 'vue-js', 'tailwind-css', 'php'],
                'ago' => '2 weeks',
            ],
            [
                'fr' => [
                    'title' => 'Comment Aurora transforme la gestion de votre entreprise',
                    'slug' => 'aurora-transforme-gestion-entreprise',
                    'excerpt' => "Retour d'expérience après 6 mois d'utilisation — témoignage d'un dirigeant de PME.",
                    'blocks' => [
                        ['type' => 'header',    'data' => ['text' => '6 mois avec Aurora : notre bilan', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Avant Aurora, notre équipe jonglait entre 4 outils différents pour gérer les clients, les stocks, les commandes et la facturation. '.self::LOREM]],
                        ['type' => 'image',     'data' => ['file' => ['url' => $u3, 'width' => 1200, 'height' => 800], 'caption' => "L'équipe Aurora Tech dans leurs nouveaux locaux", 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                        ['type' => 'header',    'data' => ['text' => 'Ce qui a changé', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => "Le premier bénéfice immédiat : la centralisation des données. Un seul endroit pour trouver l'historique d'un client, ses commandes, ses factures. ".self::LOREM]],
                        ['type' => 'image',     'data' => ['file' => ['url' => $u2, 'width' => 800, 'height' => 1000], 'caption' => 'Tableau de bord CRM Aurora — pipeline deals', 'withBorder' => false, 'withBackground' => false, 'stretched' => false]],
                        ['type' => 'header',    'data' => ['text' => 'Le module GED', 'level' => 3]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Tous nos contrats, guides techniques et supports marketing sont maintenant centralisés. La recherche par catégorie nous fait gagner un temps précieux. '.self::LOREM]],
                    ],
                ],
                'en' => [
                    'title' => 'How Aurora Transforms Your Business Management',
                    'slug' => 'aurora-transforms-business-management',
                    'excerpt' => 'A 6-month experience report — testimonial from an SME founder.',
                    'blocks' => [
                        ['type' => 'header',    'data' => ['text' => '6 months with Aurora: our review', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                        ['type' => 'image',     'data' => ['file' => ['url' => $u3, 'width' => 1200, 'height' => 800], 'caption' => 'Aurora Tech team at the office', 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                        ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ],
                ],
                'media' => $media[2] ?? null,
                'terms' => ['etudes-de-cas'],
                'ago' => '1 week',
            ],
        ];

        foreach ($bilingualDefs as $def) {
            $post = new Post();
            $post->setPostType($postType)
                 ->setStatus(PostStatusEnum::Published)
                 ->setPublishedAt(new DateTimeImmutable('-'.$def['ago']))
                 ->setFeaturedMedia($def['media'] ?? null);
            $tag($post, $def['terms'], $terms);

            foreach (['fr', 'en'] as $locale) {
                $loc = $def[$locale];
                $tr = new PostTranslation();
                $tr->setPost($post)->setLocale($locale)
                   ->setTitle($loc['title'])->setSlug($loc['slug'])
                   ->setBlocks($loc['blocks'])
                   ->setSearchContent($this->blocksText($loc['blocks']));
                if ('fr' === $locale) {
                    $tr->setMetaDescription($loc['excerpt']);
                }

                if ('fr' === $locale && null !== $def['media']) {
                    $tr->setOgImage($def['media']);
                }

                $em->persist($tr);
            }

            $em->persist($post);
            $createdPosts[] = $post;
        }

        // French-only posts — richer variety to showcase taxonomy filtering
        $img0 = isset($media[0]) ? $this->mediaUrlGenerator->publicUrl($media[0]) : '';
        $img1 = isset($media[1]) ? $this->mediaUrlGenerator->publicUrl($media[1]) : '';
        $img2 = isset($media[2]) ? $this->mediaUrlGenerator->publicUrl($media[2]) : '';
        $img3 = isset($media[3]) ? $this->mediaUrlGenerator->publicUrl($media[3]) : '';

        $frDefs = [
            [
                'title' => 'Retour sur Aurora Tech Day 2025',
                'slug' => 'aurora-tech-day-2025',
                'media' => $media[3] ?? null,
                'ago' => '3 days',
                'terms' => ['actualites', 'open-source'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => "Une journée dédiée à l'innovation", 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => 'Plus de 200 développeurs et dirigeants réunis pour découvrir les nouveautés Aurora. '.self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img3, 'width' => 1200, 'height' => 800], 'caption' => 'Aurora Tech Day 2025 — Grande salle des conférences', 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                    ['type' => 'header',    'data' => ['text' => 'Les annonces phares', 'level' => 3]],
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
                'terms' => ['produit', 'actualites', 'nouveaute'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => 'Notre vision pour les 18 prochains mois', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => "Nous avons écouté vos retours. Voici les priorités qui guideront le développement d'Aurora jusqu'en 2026. ".self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img0, 'width' => 1280, 'height' => 853], 'caption' => 'Feuille de route Aurora 2025-2026', 'withBorder' => false, 'withBackground' => true, 'stretched' => false]],
                    ['type' => 'header',    'data' => ['text' => 'Module Suivi & Workflow', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'header',    'data' => ['text' => 'Intelligence artificielle intégrée', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            [
                'title' => 'Tutoriel : créer votre premier module client Aurora',
                'slug' => 'tutoriel-premier-module-client',
                'media' => $media[1] ?? null,
                'ago' => '5 days',
                'terms' => ['tutoriels', 'symfony', 'php', 'documentation'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => 'Prérequis', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => 'Aurora est installé, vous avez un projet client. Maintenant, créons un module sur-mesure. '.self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img1, 'width' => 1280, 'height' => 720], 'caption' => "Structure d'un module Aurora", 'withBorder' => true, 'withBackground' => false, 'stretched' => false]],
                    ['type' => 'header',    'data' => ['text' => "Étape 1 : Créer l'entité", 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'header',    'data' => ['text' => 'Étape 2 : Le composant Vue', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img2, 'width' => 800, 'height' => 1000], 'caption' => "Le résultat final dans l'admin", 'withBorder' => false, 'withBackground' => false, 'stretched' => false]],
                ],
            ],
            [
                'title' => "Aurora & l'IA : automatisez vos processus métier",
                'slug' => 'aurora-ia-automatisation-processus',
                'media' => $media[2] ?? null,
                'ago' => '2 days',
                'terms' => ['produit', 'nouveaute', 'php'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => "L'IA au service de la productivité", 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img2, 'width' => 800, 'height' => 1000], 'caption' => 'Interface Aurora avec suggestions IA', 'withBorder' => false, 'withBackground' => false, 'stretched' => false]],
                    ['type' => 'header',    'data' => ['text' => 'OCR et extraction de données', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            [
                'title' => 'Guide : Sécuriser Aurora en production',
                'slug' => 'guide-securiser-aurora-production',
                'media' => $media[0] ?? null,
                'ago' => '15 days',
                'terms' => ['tutoriels', 'devops', 'php', 'documentation'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => 'Checklist sécurité production', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img0, 'width' => 1280, 'height' => 853], 'caption' => 'Dashboard monitoring Aurora', 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            // Additional posts to make taxonomy filtering compelling
            [
                'title' => 'Vue.js 3 Composition API : guide complet pour débutants',
                'slug' => 'vuejs-3-composition-api-guide',
                'media' => $media[2] ?? null,
                'ago' => '6 days',
                'terms' => ['tutoriels', 'vue-js', 'tailwind-css', 'documentation'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => 'Pourquoi la Composition API ?', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => "Vue.js 3 introduit la Composition API comme alternative plus flexible et testable à l'Options API. ".self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img2, 'width' => 800, 'height' => 1000], 'caption' => 'Exemple de composable Vue.js 3', 'withBorder' => true, 'withBackground' => false, 'stretched' => false]],
                    ['type' => 'header',    'data' => ['text' => 'ref() et reactive() : les bases', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'header',    'data' => ['text' => 'Créer un composable réutilisable', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            [
                'title' => 'PostgreSQL pour les développeurs PHP : optimisation avancée',
                'slug' => 'postgresql-developpeurs-php-optimisation',
                'media' => $media[1] ?? null,
                'ago' => '20 days',
                'terms' => ['tutoriels', 'postgresql', 'php', 'documentation'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => 'Index, requêtes et performances', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => 'PostgreSQL offre des fonctionnalités avancées qui font toute la différence en production. '.self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img1, 'width' => 1280, 'height' => 720], 'caption' => 'Explain analyze sur une requête complexe', 'withBorder' => true, 'withBackground' => false, 'stretched' => false]],
                    ['type' => 'header',    'data' => ['text' => 'JSONB et recherche full-text', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'header',    'data' => ['text' => 'Migrations sans downtime', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            [
                'title' => 'Déploiement Aurora avec Docker & CI/CD GitHub Actions',
                'slug' => 'deploiement-aurora-docker-cicd',
                'media' => $media[0] ?? null,
                'ago' => '12 days',
                'terms' => ['tutoriels', 'devops', 'open-source', 'documentation'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => 'Infrastructure as Code pour Aurora', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img0, 'width' => 1280, 'height' => 853], 'caption' => 'Pipeline CI/CD Aurora sur GitHub Actions', 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                    ['type' => 'header',    'data' => ['text' => 'Docker Compose en production', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'header',    'data' => ['text' => "Rollback automatique en cas d'erreur", 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            [
                'title' => 'Étude de cas : BioMed France migre vers Aurora',
                'slug' => 'etude-de-cas-biomed-france-aurora',
                'media' => $media[3] ?? null,
                'ago' => '18 days',
                'terms' => ['etudes-de-cas', 'postgresql', 'symfony'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => 'Contexte : une PME de santé face à ses outils', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => 'BioMed France gère 45 collaborateurs, un catalogue de 800 références et des dizaines de clients grands comptes. '.self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img3, 'width' => 1200, 'height' => 800], 'caption' => 'Locaux BioMed France à Marseille', 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                    ['type' => 'header',    'data' => ['text' => 'Résultats après 8 mois', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            [
                'title' => 'Webinar Aurora — démo ERP + Facturation en direct',
                'slug' => 'webinar-aurora-demo-erp-facturation',
                'media' => $media[1] ?? null,
                'ago' => '1 day',
                'terms' => ['actualites', 'webinar', 'nouveaute'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => 'Rejoignez-nous pour 90 minutes de démo live', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img1, 'width' => 1280, 'height' => 720], 'caption' => 'Capture écran du webinar précédent', 'withBorder' => false, 'withBackground' => false, 'stretched' => false]],
                    ['type' => 'header',    'data' => ['text' => 'Au programme', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            [
                'title' => 'Template : cahier des charges pour un projet Aurora',
                'slug' => 'template-cahier-des-charges-aurora',
                'media' => $media[2] ?? null,
                'ago' => '25 days',
                'terms' => ['produit', 'template', 'documentation'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => 'Un point de départ pour vos projets', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => 'Ce template couvre les sections clés : périmètre fonctionnel, intégrations, hébergement, planning. '.self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img2, 'width' => 800, 'height' => 1000], 'caption' => 'Extrait du template de cahier des charges', 'withBorder' => true, 'withBackground' => false, 'stretched' => false]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            [
                'title' => 'Sécuriser une API Symfony avec JWT et API Platform',
                'slug' => 'securiser-api-symfony-jwt',
                'media' => $media[0] ?? null,
                'ago' => '8 days',
                'terms' => ['tutoriels', 'symfony', 'php', 'devops'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => 'Authentification stateless avec JWT', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img0, 'width' => 1280, 'height' => 853], 'caption' => 'Diagramme flux JWT + Refresh Token', 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                    ['type' => 'header',    'data' => ['text' => 'Intégration avec Aurora', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
            [
                'title' => 'Aurora est désormais open source — rejoignez la communauté',
                'slug' => 'aurora-open-source-communaute',
                'media' => $media[3] ?? null,
                'ago' => '4 days',
                'terms' => ['actualites', 'open-source', 'nouveaute'],
                'blocks' => [
                    ['type' => 'header',    'data' => ['text' => 'Une décision qui change tout', 'level' => 2]],
                    ['type' => 'paragraph', 'data' => ['text' => 'Après deux ans de développement interne, Aurora passe en open source sous licence MIT. '.self::LOREM]],
                    ['type' => 'image',     'data' => ['file' => ['url' => $img3, 'width' => 1200, 'height' => 800], 'caption' => "L'équipe Aurora célèbre le passage en open source", 'withBorder' => false, 'withBackground' => false, 'stretched' => true]],
                    ['type' => 'header',    'data' => ['text' => 'Comment contribuer', 'level' => 3]],
                    ['type' => 'paragraph', 'data' => ['text' => self::LOREM]],
                ],
            ],
        ];

        foreach ($frDefs as $def) {
            $post = new Post();
            $post->setPostType($postType)
                 ->setStatus(PostStatusEnum::Published)
                 ->setPublishedAt(new DateTimeImmutable('-'.$def['ago']))
                 ->setFeaturedMedia($def['media'] ?? null);
            $tag($post, $def['terms'], $terms);

            $tr = new PostTranslation();
            $tr->setPost($post)->setLocale('fr')
               ->setTitle($def['title'])->setSlug($def['slug'])
               ->setBlocks($def['blocks'])
               ->setSearchContent($this->blocksText($def['blocks']));
            if (null !== $def['media']) {
                $tr->setOgImage($def['media']);
            }

            $em->persist($tr);
            $em->persist($post);
            $createdPosts[] = $post;
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
        /**
         * Form definitions.
         *
         * Structure:
         *   slug_fr / slug_en   : per-locale slugs
         *   fr / en             : translated title
         *   crmSync             : bool — create CRM contact on submission
         *   webhookUrl          : ?string
         *   steps               : list<{fr,en}> — multi-step labels (null = single page)
         *   fields              : list of field definitions
         *     key               : internal reference for conditions
         *     type / fr / en / ph_fr / ph_en / req / opts_fr / opts_en
         *     step              : int step index (0-based), null = no step
         *     conditions_def    : list<{fieldKey, operator, value}> — resolved after flush
         *     conditionsLogic   : 'and'|'or'
         *   submissions         : list<{labelFr => value}>
         */
        $formDefs = [
            // ── 1. Contact (with CRM sync) ─────────────────────────────────────
            [
                'slug_fr' => 'nous-contacter',
                'slug_en' => 'contact',
                'fr' => 'Nous contacter',
                'en' => 'Contact Us',
                'crmSync' => true,
                'webhookUrl' => null,
                'steps' => null,
                'fields' => [
                    ['key' => 'nom',     'type' => FormFieldTypeEnum::Text,     'fr' => 'Nom complet',   'en' => 'Full name',     'ph_fr' => 'Jean Dupont',           'ph_en' => 'John Doe',          'req' => true],
                    ['key' => 'email',   'type' => FormFieldTypeEnum::Email,    'fr' => 'Adresse email', 'en' => 'Email address', 'ph_fr' => 'jean@exemple.fr',       'ph_en' => 'john@example.com',  'req' => true],
                    ['key' => 'tel',     'type' => FormFieldTypeEnum::Tel,      'fr' => 'Téléphone',     'en' => 'Phone',         'ph_fr' => '+33 6 00 00 00 00',     'ph_en' => '+1 555 000 0000',   'req' => false],
                    ['key' => 'sujet',   'type' => FormFieldTypeEnum::Select,   'fr' => 'Sujet',         'en' => 'Subject',       'ph_fr' => 'Choisissez un sujet',   'ph_en' => 'Choose a subject',  'req' => true,
                        'opts_fr' => ['Demande commerciale', 'Support technique', 'Partenariat', 'Autre'],
                        'opts_en' => ['Sales inquiry', 'Technical support', 'Partnership', 'Other']],
                    ['key' => 'message', 'type' => FormFieldTypeEnum::Textarea, 'fr' => 'Message',       'en' => 'Message',       'ph_fr' => 'Votre message…',        'ph_en' => 'Your message…',     'req' => true],
                ],
                'submissions' => [
                    ['Nom complet' => 'Pierre Dubois',   'Adresse email' => 'pierre.dubois@tech-innovation.fr', 'Téléphone' => '+33 6 12 34 56 78', 'Sujet' => 'Demande commerciale', 'Message' => "Bonjour, nous souhaitons migrer notre outil CRM actuel vers Aurora. Pouvez-vous nous envoyer un devis pour 10 utilisateurs avec le module GED inclus ? Merci d'avance."],
                    ['Nom complet' => 'Camille Leroy',   'Adresse email' => 'c.leroy@biomed-france.com',        'Téléphone' => '+33 6 23 45 67 89', 'Sujet' => 'Support technique',   'Message' => "Depuis la mise à jour de vendredi, nos données CRM ne se synchronisent plus correctement avec l'ERP. Les stocks ne sont plus à jour côté e-commerce. Urgence niveau 2."],
                    ['Nom complet' => 'François Moreau', 'Adresse email' => 'f.moreau@retail-connect.fr',       'Téléphone' => '+33 6 34 56 78 90', 'Sujet' => 'Partenariat',         'Message' => "Nous sommes un intégrateur spécialisé en transformation digitale pour les réseaux de distribution. Aurora correspond parfaitement à nos besoins clients. Pouvons-nous discuter d'un partenariat revendeur ?"],
                    ['Nom complet' => 'Julie Chen',      'Adresse email' => 'julie.chen@tech-innovation.fr',    'Téléphone' => '',                  'Sujet' => 'Demande commerciale', 'Message' => 'Suite à notre démo de la semaine dernière, mon équipe est convaincue. Nous aimerions démarrer avec la Suite Complète Aurora pour 15 utilisateurs. Quelles sont les prochaines étapes ?'],
                    ['Nom complet' => 'Isabelle Renard', 'Adresse email' => 'i.renard@nexus-digital.fr',        'Téléphone' => '+33 6 67 89 01 23', 'Sujet' => 'Autre',               'Message' => "Bonjour, je cherche à intégrer Aurora dans notre stack Vercel + Next.js via API. Disposez-vous d'une documentation sur votre API REST et les webhooks disponibles ?"],
                    ['Nom complet' => 'David Beaumont',  'Adresse email' => 'd.beaumont@leclerc-nord.fr',       'Téléphone' => '+33 6 78 90 12 34', 'Sujet' => 'Support technique',   'Message' => "Le module Billing plante lors de l'import OCR de factures PDF multi-pages. Log d'erreur joint. Merci de traiter en priorité car nous avons 200+ factures en attente de traitement."],
                ],
            ],

            // ── 2. Newsletter (CRM sync — French-localized) ────────────────────
            [
                'slug_fr' => 'inscription-newsletter',
                'slug_en' => 'newsletter',
                'fr' => 'Inscription à la newsletter',
                'en' => 'Newsletter Sign-up',
                'crmSync' => true,
                'webhookUrl' => null,
                'steps' => null,
                'fields' => [
                    ['key' => 'email',   'type' => FormFieldTypeEnum::Email,    'fr' => 'Votre email', 'en' => 'Your email',      'ph_fr' => 'vous@exemple.fr', 'ph_en' => 'you@example.com', 'req' => true],
                    ['key' => 'prenom',  'type' => FormFieldTypeEnum::Text,     'fr' => 'Prénom',      'en' => 'First name',      'ph_fr' => 'Prénom',          'ph_en' => 'First name',      'req' => false],
                    ['key' => 'consent', 'type' => FormFieldTypeEnum::Checkbox, 'fr' => "J'accepte de recevoir les communications Aurora", 'en' => 'I agree to receive Aurora communications', 'ph_fr' => '', 'ph_en' => '', 'req' => true],
                ],
                'submissions' => [
                    ['Votre email' => 'julie.martin@gmail.com',     'Prénom' => 'Julie',    "J'accepte de recevoir les communications Aurora" => '1'],
                    ['Votre email' => 'marc.fontaine@outlook.fr',   'Prénom' => 'Marc',     "J'accepte de recevoir les communications Aurora" => '1'],
                    ['Votre email' => 'sophie.b@yahoo.fr',          'Prénom' => 'Sophie',   "J'accepte de recevoir les communications Aurora" => '1'],
                    ['Votre email' => 'thomas.dev@protonmail.com',  'Prénom' => 'Thomas',   "J'accepte de recevoir les communications Aurora" => '1'],
                    ['Votre email' => 'alice.design@gmail.com',     'Prénom' => 'Alice',    "J'accepte de recevoir les communications Aurora" => '1'],
                    ['Votre email' => 'hugo.cto@startupfactory.fr', 'Prénom' => 'Hugo',     "J'accepte de recevoir les communications Aurora" => '1'],
                    ['Votre email' => 'nathalie.rh@clinique-sj.fr', 'Prénom' => 'Nathalie', "J'accepte de recevoir les communications Aurora" => '1'],
                    ['Votre email' => 'pierre.pdg@ecobuilding.fr',  'Prénom' => 'Pierre',   "J'accepte de recevoir les communications Aurora" => '1'],
                ],
            ],

            // ── 3. Demande de devis — multi-step ──────────────────────────────
            [
                'slug_fr' => 'demande-de-devis',
                'slug_en' => 'request-quote',
                'fr' => 'Demande de devis',
                'en' => 'Request a Quote',
                'crmSync' => true,
                'webhookUrl' => null,
                'steps' => [
                    ['fr' => 'Vos coordonnées', 'en' => 'Your details'],
                    ['fr' => 'Votre projet',     'en' => 'Your project'],
                ],
                'fields' => [
                    ['key' => 'nom',      'type' => FormFieldTypeEnum::Text,     'fr' => 'Nom complet',            'en' => 'Full name',           'ph_fr' => 'Jean Dupont',          'ph_en' => 'John Doe',         'req' => true,  'step' => 0],
                    ['key' => 'email',    'type' => FormFieldTypeEnum::Email,    'fr' => 'Adresse email',          'en' => 'Email address',       'ph_fr' => 'jean@exemple.fr',      'ph_en' => 'john@example.com', 'req' => true,  'step' => 0],
                    ['key' => 'tel',      'type' => FormFieldTypeEnum::Tel,      'fr' => 'Téléphone',              'en' => 'Phone',               'ph_fr' => '+33 6 00 00 00 00',    'ph_en' => '+1 555 000 0000',  'req' => false, 'step' => 0],
                    ['key' => 'service',  'type' => FormFieldTypeEnum::Select,   'fr' => 'Type de prestation',     'en' => 'Service type',        'ph_fr' => '',                     'ph_en' => '',                 'req' => true,  'step' => 1,
                        'opts_fr' => ['Développement web', 'Conseil & architecture', 'Formation', 'Intégration Aurora', 'Autre'],
                        'opts_en' => ['Web development', 'Consulting & architecture', 'Training', 'Aurora integration', 'Other']],
                    ['key' => 'budget',   'type' => FormFieldTypeEnum::Select,   'fr' => 'Budget estimé',          'en' => 'Estimated budget',    'ph_fr' => '',                     'ph_en' => '',                 'req' => false, 'step' => 1,
                        'opts_fr' => ['< 5 000 €', '5 000 – 15 000 €', '15 000 – 50 000 €', '> 50 000 €'],
                        'opts_en' => ['< €5,000', '€5,000 – €15,000', '€15,000 – €50,000', '> €50,000']],
                    ['key' => 'projet',   'type' => FormFieldTypeEnum::Textarea, 'fr' => 'Décrivez votre projet',  'en' => 'Describe your project', 'ph_fr' => 'Contexte, objectifs, contraintes…', 'ph_en' => 'Context, goals, constraints…', 'req' => true, 'step' => 1],
                ],
                'submissions' => [
                    ['Nom complet' => 'Antoine Garnier', 'Adresse email' => 'a.garnier@fintech-horizons.fr', 'Téléphone' => '+33 6 90 12 34 56', 'Type de prestation' => 'Intégration Aurora', 'Budget estimé' => '15 000 – 50 000 €', 'Décrivez votre projet' => "Nous souhaitons intégrer Aurora dans notre système de gestion de portefeuille clients. Besoin d'une interface personnalisée pour nos conseillers financiers."],
                    ['Nom complet' => 'Laure Michaud',   'Adresse email' => 'l.michaud@ecobuilding.fr',      'Téléphone' => '+33 6 01 23 45 67', 'Type de prestation' => 'Développement web',  'Budget estimé' => '5 000 – 15 000 €',  'Décrivez votre projet' => "Refonte complète de notre site corporate avec intégration Aurora pour la gestion des appels d'offres et documents contractuels."],
                    ['Nom complet' => 'Emma Rousseau',   'Adresse email' => 'e.rousseau@startupfactory.fr',  'Téléphone' => '',                  'Type de prestation' => 'Formation',          'Budget estimé' => '< 5 000 €',         'Décrivez votre projet' => "Formation d'une équipe de 8 personnes sur Aurora — modules CRM, GED et facturation. Idéalement en présentiel sur Paris."],
                ],
            ],

            // ── 4. Satisfaction — conditional fields ──────────────────────────
            [
                'slug_fr' => 'satisfaction',
                'slug_en' => 'satisfaction',
                'fr' => 'Enquête de satisfaction',
                'en' => 'Satisfaction Survey',
                'crmSync' => false,
                'webhookUrl' => null,
                'steps' => null,
                'fields' => [
                    ['key' => 'recommande', 'type' => FormFieldTypeEnum::Radio,    'fr' => 'Recommanderiez-vous Aurora à un collègue ?', 'en' => 'Would you recommend Aurora to a colleague?', 'ph_fr' => '', 'ph_en' => '', 'req' => true,
                        'opts_fr' => ['Oui, sans hésitation', 'Probablement', 'Non'],
                        'opts_en' => ['Yes, absolutely', 'Probably', 'No']],
                    // Shown only when "Non" is selected — conditions_def resolved after flush
                    ['key' => 'pourquoi_non', 'type' => FormFieldTypeEnum::Textarea, 'fr' => 'Pourquoi pas ?', 'en' => 'Why not?', 'ph_fr' => "Qu'est-ce qui vous a déçu ?", 'ph_en' => 'What disappointed you?', 'req' => false,
                        'conditions_def' => [['fieldKey' => 'recommande', 'operator' => 'eq', 'value' => 'Non']],
                        'conditionsLogic' => 'and'],
                    ['key' => 'source', 'type' => FormFieldTypeEnum::Select,   'fr' => 'Comment nous avez-vous trouvé ?', 'en' => 'How did you find us?', 'ph_fr' => '', 'ph_en' => '', 'req' => false,
                        'opts_fr' => ['Bouche-à-oreille', 'Moteur de recherche', 'Réseaux sociaux', 'Conférence / événement', 'Autre'],
                        'opts_en' => ['Word of mouth', 'Search engine', 'Social media', 'Conference / event', 'Other']],
                    // Shown only when "Autre" is selected
                    ['key' => 'source_autre', 'type' => FormFieldTypeEnum::Text, 'fr' => 'Précisez la source', 'en' => 'Please specify the source', 'ph_fr' => 'ex : podcast, presse…', 'ph_en' => 'e.g. podcast, press…', 'req' => false,
                        'conditions_def' => [['fieldKey' => 'source', 'operator' => 'eq', 'value' => 'Autre']],
                        'conditionsLogic' => 'and'],
                    ['key' => 'commentaire', 'type' => FormFieldTypeEnum::Textarea, 'fr' => 'Commentaire libre', 'en' => 'Additional comments', 'ph_fr' => 'Vos suggestions, remarques…', 'ph_en' => 'Your suggestions, remarks…', 'req' => false],
                ],
                'submissions' => [
                    ['Recommanderiez-vous Aurora à un collègue ?' => 'Oui, sans hésitation', 'Comment nous avez-vous trouvé ?' => 'Bouche-à-oreille',           'Commentaire libre' => 'Excellent outil, très bien intégré. Le module CRM est particulièrement efficace.'],
                    ['Recommanderiez-vous Aurora à un collègue ?' => 'Probablement',          'Comment nous avez-vous trouvé ?' => 'Moteur de recherche',          'Commentaire libre' => "L'interface est intuitive mais quelques options avancées mériteraient plus de documentation."],
                    ['Recommanderiez-vous Aurora à un collègue ?' => 'Non',                   'Pourquoi pas ?' => 'Le module de facturation manque encore de fonctionnalités pour notre secteur (BTP). On attend avec impatience les prochaines mises à jour.', 'Comment nous avez-vous trouvé ?' => 'Conférence / événement', 'Commentaire libre' => ''],
                    ['Recommanderiez-vous Aurora à un collègue ?' => 'Oui, sans hésitation', 'Comment nous avez-vous trouvé ?' => 'Autre', 'Précisez la source' => 'Article dans le magazine Développez.com', 'Commentaire libre' => 'Super découverte !'],
                    ['Recommanderiez-vous Aurora à un collègue ?' => 'Probablement',          'Comment nous avez-vous trouvé ?' => 'Réseaux sociaux',              'Commentaire libre' => "Bon produit dans l'ensemble. La courbe d'apprentissage est un peu longue au démarrage."],
                ],
            ],
        ];

        foreach ($formDefs as $fd) {
            $form = new Form();
            $form->setCrmSync($fd['crmSync'] ?? false);
            $form->setWebhookUrl($fd['webhookUrl'] ?? null);
            $form->setSteps($fd['steps'] ?? null);
            $em->persist($form);

            foreach (['fr', 'en'] as $locale) {
                $ft = new FormTranslation();
                $ft->setForm($form)
                   ->setLocale($locale)
                   ->setTitle($fd[$locale])
                   ->setSlug('fr' === $locale ? $fd['slug_fr'] : $fd['slug_en']);
                $em->persist($ft);
            }

            // Build fields — keep two maps: labelFr → field, key → field
            $fieldsByLabel = [];
            $fieldsByKey = [];
            foreach ($fd['fields'] as $pos => $fieldDef) {
                $field = new FormField();
                $field->setForm($form)
                      ->setType($fieldDef['type'])
                      ->setRequired($fieldDef['req'])
                      ->setPosition($pos)
                      ->setStep($fieldDef['step'] ?? null)
                      ->setConditionsLogic($fieldDef['conditionsLogic'] ?? 'and');
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

                $fieldsByLabel[$fieldDef['fr']] = $field;
                if (isset($fieldDef['key'])) {
                    $fieldsByKey[$fieldDef['key']] = $field;
                }
            }

            // Flush to get field IDs — needed for conditions AND submissions
            $em->flush();

            // Resolve conditions_def → real field IDs, then persist
            foreach ($fd['fields'] as $fieldDef) {
                if (empty($fieldDef['conditions_def'])) {
                    continue;
                }

                if (!isset($fieldDef['key'], $fieldsByKey[$fieldDef['key']])) {
                    continue;
                }

                $conditions = [];
                foreach ($fieldDef['conditions_def'] as $condDef) {
                    $targetField = $fieldsByKey[$condDef['fieldKey']] ?? null;
                    if (!$targetField instanceof FormField) {
                        continue;
                    }

                    $conditions[] = [
                        'fieldId' => $targetField->getId(),
                        'operator' => $condDef['operator'],
                        'value' => $condDef['value'],
                    ];
                }

                if ([] !== $conditions) {
                    $fieldsByKey[$fieldDef['key']]->setConditions($conditions);
                }
            }

            $em->flush();

            // Submissions
            foreach ($fd['submissions'] as $sub) {
                $data = [];
                foreach ($sub as $label => $value) {
                    if (isset($fieldsByLabel[$label])) {
                        $data[(string) $fieldsByLabel[$label]->getId()] = $value;
                    }
                }

                $fs = new FormSubmission();
                $fs->setForm($form)->setData($data)->setLocale('fr');
                $em->persist($fs);
            }
        }
    }

    // ── Taxonomies ────────────────────────────────────────────────────────────

    /**
     * Creates demo taxonomy terms and links them to the article PostType.
     *
     * @return array<string, TaxonomyTermInterface> all terms indexed by slug
     */
    private function createTaxonomies(EntityManagerInterface $em, ?PostType $postType): array
    {
        $terms = [];

        $makeTerm = static function (Taxonomy $taxonomy, string $slug, array $labels) use ($em, &$terms): TaxonomyTerm {
            $term = new TaxonomyTerm();
            $term->setTaxonomy($taxonomy);
            foreach ($labels as $locale => $name) {
                $term->translate($locale)->setName($name)->setSlug($slug);
            }

            $em->persist($term);
            $terms[$slug] = $term;

            return $term;
        };

        // ── Tag taxonomy ──────────────────────────────────────────────────────
        $tagTaxonomy = $em->getRepository(Taxonomy::class)->findOneBy(['slug' => 'tag']);
        if ($tagTaxonomy instanceof Taxonomy) {
            // Retrieve initial terms seeded by AppFixtures (nouveaute, tutoriel)
            foreach ($tagTaxonomy->getTerms() as $existing) {
                $translation = $existing->translate('fr');
                if ('' !== $translation->getSlug()) {
                    $terms[$translation->getSlug()] = $existing;
                }
            }

            foreach ([
                'symfony' => ['fr' => 'Symfony',      'en' => 'Symfony'],
                'vue-js' => ['fr' => 'Vue.js',        'en' => 'Vue.js'],
                'php' => ['fr' => 'PHP',           'en' => 'PHP'],
                'tailwind-css' => ['fr' => 'Tailwind CSS',  'en' => 'Tailwind CSS'],
                'postgresql' => ['fr' => 'PostgreSQL',    'en' => 'PostgreSQL'],
                'devops' => ['fr' => 'DevOps',        'en' => 'DevOps'],
                'open-source' => ['fr' => 'Open Source',   'en' => 'Open Source'],
            ] as $slug => $labels) {
                $makeTerm($tagTaxonomy, $slug, $labels);
            }
        }

        // ── Category taxonomy ─────────────────────────────────────────────────
        $catTaxonomy = $em->getRepository(Taxonomy::class)->findOneBy(['slug' => 'category']);
        if ($catTaxonomy instanceof Taxonomy) {
            foreach ([
                'tutoriels' => ['fr' => 'Tutoriels',       'en' => 'Tutorials'],
                'actualites' => ['fr' => 'Actualités',      'en' => 'News'],
                'etudes-de-cas' => ['fr' => 'Études de cas',   'en' => 'Case Studies'],
                'produit' => ['fr' => 'Produit',         'en' => 'Product'],
            ] as $slug => $labels) {
                $makeTerm($catTaxonomy, $slug, $labels);
            }
        }

        // ── Ressource taxonomy (new) ──────────────────────────────────────────
        $resTaxonomy = new Taxonomy();
        $resTaxonomy->setSlug('ressource')->setHierarchical(false)->setIsBuiltIn(false);
        $resTaxonomy->translate('fr')->setLabel('Ressource');
        $resTaxonomy->translate('en')->setLabel('Resource');
        if ($postType instanceof PostType) {
            $resTaxonomy->getPostTypes()->add($postType);
        }

        $em->persist($resTaxonomy);

        foreach ([
            'documentation' => ['fr' => 'Documentation', 'en' => 'Documentation'],
            'video' => ['fr' => 'Vidéo',          'en' => 'Video'],
            'webinar' => ['fr' => 'Webinaire',      'en' => 'Webinar'],
            'template' => ['fr' => 'Template',       'en' => 'Template'],
        ] as $slug => $labels) {
            $makeTerm($resTaxonomy, $slug, $labels);
        }

        return $terms;
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
            ['first' => 'Pierre',    'last' => 'Dubois',    'email' => 'pierre.dubois@tech-innovation.fr',  'phone' => '+33 6 12 34 56 78', 'company' => 0,    'source' => ContactSourceEnum::Manual, 'tags' => ['client', 'vip']],
            ['first' => 'Camille',   'last' => 'Leroy',     'email' => 'c.leroy@biomed-france.com',         'phone' => '+33 6 23 45 67 89', 'company' => 1,    'source' => ContactSourceEnum::Manual, 'tags' => ['prospect']],
            ['first' => 'François',  'last' => 'Moreau',    'email' => 'f.moreau@retail-connect.fr',        'phone' => '+33 6 34 56 78 90', 'company' => 2,    'source' => ContactSourceEnum::Order,  'tags' => ['client']],
            ['first' => 'Julie',     'last' => 'Chen',      'email' => 'julie.chen@tech-innovation.fr',     'phone' => '+33 6 45 67 89 01', 'company' => 0,    'source' => ContactSourceEnum::Manual, 'tags' => ['client']],
            ['first' => 'Marc',      'last' => 'Fontaine',  'email' => 'marc.fontaine@prospect.com',        'phone' => '+33 6 56 78 90 12', 'company' => null, 'source' => ContactSourceEnum::Form,   'tags' => ['prospect', 'newsletter']],
            ['first' => 'Isabelle',  'last' => 'Renard',    'email' => 'i.renard@nexus-digital.fr',         'phone' => '+33 6 67 89 01 23', 'company' => 3,    'source' => ContactSourceEnum::Manual, 'tags' => ['partenaire']],
            ['first' => 'David',     'last' => 'Beaumont',  'email' => 'd.beaumont@leclerc-nord.fr',        'phone' => '+33 6 78 90 12 34', 'company' => 4,    'source' => ContactSourceEnum::Manual, 'tags' => ['client']],
            ['first' => 'Nathalie',  'last' => 'Simon',     'email' => 'n.simon@clinique-sj.fr',            'phone' => '+33 6 89 01 23 45', 'company' => 5,    'source' => ContactSourceEnum::Form,   'tags' => ['prospect', 'newsletter']],
            ['first' => 'Antoine',   'last' => 'Garnier',   'email' => 'a.garnier@fintech-horizons.fr',     'phone' => '+33 6 90 12 34 56', 'company' => 6,    'source' => ContactSourceEnum::Manual, 'tags' => ['client', 'vip']],
            ['first' => 'Laure',     'last' => 'Michaud',   'email' => 'l.michaud@ecobuilding.fr',          'phone' => '+33 6 01 23 45 67', 'company' => 7,    'source' => ContactSourceEnum::Order,  'tags' => ['client']],
            ['first' => 'Sébastien', 'last' => 'Blanc',     'email' => 's.blanc@logimove.fr',               'phone' => '+33 6 12 23 34 45', 'company' => 8,    'source' => ContactSourceEnum::Manual, 'tags' => ['partenaire']],
            ['first' => 'Emma',      'last' => 'Rousseau',  'email' => 'e.rousseau@startupfactory.fr',      'phone' => '+33 6 23 34 45 56', 'company' => 9,    'source' => ContactSourceEnum::Form,   'tags' => ['prospect']],
            ['first' => 'Thomas',    'last' => 'Lambert',   'email' => 'tlambert@prospect.io',              'phone' => '+33 6 34 45 56 67', 'company' => null, 'source' => ContactSourceEnum::Form,   'tags' => ['prospect', 'newsletter']],
            ['first' => 'Céline',    'last' => 'Dupuis',    'email' => 'celine.dupuis@startup-prospect.fr', 'phone' => '+33 6 45 56 67 78', 'company' => null, 'source' => ContactSourceEnum::Order,  'tags' => ['client']],
            ['first' => 'Hugo',      'last' => 'Marchand',  'email' => 'h.marchand@tech-innovation.fr',     'phone' => '+33 6 56 67 78 89', 'company' => 0,    'source' => ContactSourceEnum::Manual, 'tags' => ['client']],
        ];
        $slugger = new AsciiSlugger();
        $contactTagsByLabel = [];
        $uniqueLabels = [];
        foreach ($contactDefs as $def) {
            foreach ($def['tags'] as $label) {
                $uniqueLabels[$label] = true;
            }
        }

        foreach (array_keys($uniqueLabels) as $label) {
            $contactTag = new ContactTag();
            $contactTag->setLabel($label)
                ->setSlug($slugger->slug($label)->lower()->toString())
                ->setColor('#6366F1');
            $em->persist($contactTag);
            $contactTagsByLabel[$label] = $contactTag;
        }

        foreach ($contactDefs as $def) {
            $c = new Contact();
            $c->setFirstName($def['first'])
              ->setLastName($def['last'])
              ->setEmail($def['email'])
              ->setPhone($def['phone'])
              ->setSource($def['source']);
            if (null !== $def['company']) {
                $c->setCompany($companies[$def['company']]);
            }

            foreach ($def['tags'] as $label) {
                $c->addContactTag($contactTagsByLabel[$label]);
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

        $em->flush();

        $this->createContactActivity($em, $contacts, $users);

        return [$companies, $contacts];
    }

    /**
     * Seeds a realistic audit trail for the demo contacts so the activity
     * timeline in the contact detail modal isn't empty on a fresh install.
     *
     * Each tuple is: [contactIndex, action, daysAgo, actorUserIndex|null].
     * `null` actor = system event (form submission, order sync).
     *
     * @param Contact[] $contacts
     * @param User[]    $users
     */
    private function createContactActivity(EntityManagerInterface $em, array $contacts, array $users): void
    {
        $now = new DateTimeImmutable();
        $events = [
            // Pierre (VIP, manual) — long-running client relationship
            [0, 'contact.created', 142, 0],
            [0, 'contact.updated', 96,  0],
            [0, 'contact.updated', 41,  1],
            [0, 'contact.updated', 4,   0],
            // Camille (prospect, manual)
            [1, 'contact.created', 73,  1],
            [1, 'contact.updated', 21,  1],
            // François (order)
            [2, 'contact.created', 38,  null],
            [2, 'contact.updated', 9,   0],
            // Julie (client, manual)
            [3, 'contact.created', 110, 0],
            [3, 'contact.updated', 60,  0],
            [3, 'contact.updated', 12,  1],
            // Marc (form, newsletter)
            [4, 'contact.created', 22,  null],
            [4, 'contact.updated', 6,   1],
            // Isabelle (partenaire)
            [5, 'contact.created', 65,  0],
            // David (client)
            [6, 'contact.created', 58,  1],
            [6, 'contact.updated', 14,  0],
            // Nathalie (form)
            [7, 'contact.created', 11,  null],
            // Antoine (VIP)
            [8, 'contact.created', 130, 0],
            [8, 'contact.updated', 76,  0],
            [8, 'contact.updated', 18,  1],
            [8, 'contact.updated', 1,   0],
            // Laure (order)
            [9, 'contact.created', 27,  null],
            [9, 'contact.updated', 5,   0],
            // Sébastien (partenaire)
            [10, 'contact.created', 48, 1],
            // Emma (form)
            [11, 'contact.created', 17, null],
            // Thomas (form)
            [12, 'contact.created', 9,  null],
            [12, 'contact.updated', 3,  0],
            // Céline (order)
            [13, 'contact.created', 4,  null],
            // Hugo (client tech-innov)
            [14, 'contact.created', 84, 0],
            [14, 'contact.updated', 30, 1],
        ];

        foreach ($events as [$contactIndex, $action, $daysAgo, $actorIndex]) {
            $contact = $contacts[$contactIndex];
            $actor = null !== $actorIndex ? ($users[$actorIndex] ?? null) : null;

            $log = new AuditLog(
                module: 'crm',
                action: $action,
                entityType: 'Contact',
                entityId: $contact->getId(),
                userId: $actor?->getId(),
                userEmail: $actor?->getEmail(),
                userName: $actor?->getName(),
                data: [
                    'name' => $contact->getFullName(),
                    'reference' => $contact->getReference(),
                    'source' => $contact->getSource()?->value,
                ],
            );

            $createdAt = $now->modify('-'.$daysAgo.' days');
            $reflection = new ReflectionProperty(AbstractAuditLog::class, 'createdAt');
            $reflection->setValue($log, $createdAt);

            $em->persist($log);
        }

        $em->flush();
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

            // Cycle through image media so every product gets a visual.
            $imageMedia = array_values(array_filter($media, static fn ($m): bool => str_starts_with((string) $m->getMimeType(), 'image/')));
            if ([] !== $imageMedia) {
                $p->setImage($imageMedia[$i % count($imageMedia)]);
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

    // ── Listing Categories ────────────────────────────────────────────────────

    /**
     * @param Listing[] $listings
     */
    private function createListingCategories(EntityManagerInterface $em, array $listings): void
    {
        $categoryDefs = [
            [
                'key' => 'apparel',
                'parent' => null,
                'position' => 0,
                'translations' => [
                    'fr' => ['name' => 'Vêtements', 'slug' => 'vetements', 'description' => 'Toute notre sélection de vêtements.', 'seoTitle' => 'Vêtements — Aurora', 'seoDescription' => 'Découvrez la collection de vêtements Aurora.'],
                    'en' => ['name' => 'Apparel', 'slug' => 'apparel', 'description' => 'Our full apparel collection.', 'seoTitle' => 'Apparel — Aurora', 'seoDescription' => 'Discover the Aurora apparel collection.'],
                ],
            ],
            [
                'key' => 'men',
                'parent' => 'apparel',
                'position' => 0,
                'translations' => [
                    'fr' => ['name' => 'Hommes', 'slug' => 'hommes', 'description' => 'Vêtements pour hommes.', 'seoTitle' => 'Hommes — Aurora', 'seoDescription' => 'Vêtements pour hommes.'],
                    'en' => ['name' => 'Men', 'slug' => 'men', 'description' => 'Apparel for men.', 'seoTitle' => 'Men — Aurora', 'seoDescription' => 'Apparel for men.'],
                ],
            ],
            [
                'key' => 'women',
                'parent' => 'apparel',
                'position' => 1,
                'translations' => [
                    'fr' => ['name' => 'Femmes', 'slug' => 'femmes', 'description' => 'Vêtements pour femmes.', 'seoTitle' => 'Femmes — Aurora', 'seoDescription' => 'Vêtements pour femmes.'],
                    'en' => ['name' => 'Women', 'slug' => 'women', 'description' => 'Apparel for women.', 'seoTitle' => 'Women — Aurora', 'seoDescription' => 'Apparel for women.'],
                ],
            ],
            [
                'key' => 'accessories',
                'parent' => null,
                'position' => 1,
                'translations' => [
                    'fr' => ['name' => 'Accessoires', 'slug' => 'accessoires', 'description' => 'Accessoires et compléments.', 'seoTitle' => 'Accessoires — Aurora', 'seoDescription' => 'Accessoires et compléments Aurora.'],
                    'en' => ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Accessories and add-ons.', 'seoTitle' => 'Accessories — Aurora', 'seoDescription' => 'Aurora accessories and add-ons.'],
                ],
            ],
        ];

        $categories = [];
        foreach ($categoryDefs as $def) {
            $category = new ListingCategory();
            $category->setPosition($def['position']);
            $category->setVisible(true);

            if (null !== $def['parent']) {
                $category->setParent($categories[$def['parent']]);
            }

            foreach ($def['translations'] as $locale => $t) {
                $translation = new ListingCategoryTranslation();
                $translation->setLocale($locale)
                    ->setName($t['name'])
                    ->setSlug($t['slug'])
                    ->setDescription($t['description'])
                    ->setSeoTitle($t['seoTitle'])
                    ->setSeoDescription($t['seoDescription'])
                    ->setCategory($category);
                $category->addTranslation($translation);
                $em->persist($translation);
            }

            $em->persist($category);
            $categories[$def['key']] = $category;
        }

        // Distribute the existing demo listings across the leaf/standalone categories.
        // Indices map to the listingDefs order in createEcommerce(). The exact split
        // is illustrative — what matters is that each demo category has ≥1 listing.
        $assignments = [
            'men' => [0, 4, 8],         // CRM licence, NAS 4 baies, formation dev web
            'women' => [1, 5, 12],      // ERP licence, NAS 8 baies, Editorial CMS
            'accessories' => [6, 7, 11], // Station USB-C, écran 4K, onboarding
        ];

        foreach ($assignments as $categoryKey => $listingIndexes) {
            $category = $categories[$categoryKey];
            foreach ($listingIndexes as $index) {
                if (isset($listings[$index])) {
                    $listings[$index]->addCategory($category);
                }
            }
        }
    }

    // ── Listing Tags ──────────────────────────────────────────────────────────

    /**
     * @param Listing[] $listings
     */
    private function createListingTags(EntityManagerInterface $em, array $listings): void
    {
        $tagDefs = [
            [
                'key' => 'new',
                'color' => '#10B981',
                'translations' => [
                    'fr' => ['name' => 'Nouveauté', 'slug' => 'nouveaute', 'description' => 'Les dernières nouveautés Aurora.'],
                    'en' => ['name' => 'New', 'slug' => 'new', 'description' => 'The latest Aurora arrivals.'],
                ],
            ],
            [
                'key' => 'sale',
                'color' => '#EF4444',
                'translations' => [
                    'fr' => ['name' => 'Promo', 'slug' => 'promo', 'description' => 'Produits en promotion.'],
                    'en' => ['name' => 'Sale', 'slug' => 'sale', 'description' => 'Products on sale.'],
                ],
            ],
            [
                'key' => 'limited',
                'color' => '#F59E0B',
                'translations' => [
                    'fr' => ['name' => 'Édition limitée', 'slug' => 'edition-limitee', 'description' => 'Disponibles en quantité limitée.'],
                    'en' => ['name' => 'Limited edition', 'slug' => 'limited-edition', 'description' => 'Available in limited quantities.'],
                ],
            ],
            [
                'key' => 'featured',
                'color' => '#8B5CF6',
                'translations' => [
                    'fr' => ['name' => 'Coup de cœur', 'slug' => 'coup-de-coeur', 'description' => 'Nos sélections préférées.'],
                    'en' => ['name' => 'Featured', 'slug' => 'featured', 'description' => 'Our favourite picks.'],
                ],
            ],
        ];

        $tags = [];
        foreach ($tagDefs as $def) {
            $tag = new ListingTag();
            $tag->setColor($def['color']);
            $tag->setVisible(true);

            foreach ($def['translations'] as $locale => $t) {
                $translation = new ListingTagTranslation();
                $translation->setLocale($locale)
                    ->setName($t['name'])
                    ->setSlug($t['slug'])
                    ->setDescription($t['description'])
                    ->setTag($tag);
                $tag->addTranslation($translation);
                $em->persist($translation);
            }

            $em->persist($tag);
            $tags[$def['key']] = $tag;
        }

        // Distribute tags across existing demo listings. Indices map to the
        // listingDefs order in createEcommerce(). Some listings carry multiple
        // tags so the multi-tag display is exercised in the admin and frontend.
        $assignments = [
            'new' => [0, 12, 8],          // CRM licence, Editorial CMS, formation dev web
            'sale' => [4, 5, 7],          // NAS 4 baies, NAS 8 baies, écran 4K
            'limited' => [3, 6],          // Suite Complète, station USB-C
            'featured' => [0, 3, 11, 12], // CRM licence, Suite Complète, onboarding, Editorial CMS
        ];

        foreach ($assignments as $tagKey => $listingIndexes) {
            $tag = $tags[$tagKey];
            foreach ($listingIndexes as $index) {
                if (isset($listings[$index])) {
                    $listings[$index]->addTag($tag);
                }
            }
        }
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
            ->setNumber('FAC-2026-0004')
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

        // Additional invoices for variety — numbers assigned in chronological order (art. 242 nonies A CGI)
        $extraInvoices = [
            ['ti' => 5,  'number' => 'FAC-2026-0001', 'status' => InvoiceStatusEnum::Paid,        'label' => 'Hébergement OVHcloud — Serveur dédié 3 mois', 'net' => 44700,  'gross' => 53640, 'project' => 'Infrastructure Prod',   'ago' => '-2 months', 'terms' => '30 jours net'],
            ['ti' => 6,  'number' => 'FAC-2026-0002', 'status' => InvoiceStatusEnum::Paid,        'label' => 'Google Workspace Business Plus — 10 licences', 'net' => 13200,  'gross' => 15840, 'project' => 'Outils bureautique',    'ago' => '-45 days',  'terms' => 'Mensuel'],
            ['ti' => 10, 'number' => 'FAC-2026-0003', 'status' => InvoiceStatusEnum::Paid,        'label' => 'Stripe — Commission transactions Octobre 2025', 'net' => 4200,   'gross' => 5040,  'project' => 'E-commerce',             'ago' => '-30 days',  'terms' => 'Mensuel'],
            ['ti' => 7,  'number' => 'FAC-2026-0005', 'status' => InvoiceStatusEnum::Validated,   'label' => 'AWS EC2 + RDS — Octobre 2025',                 'net' => 28600,  'gross' => 34320, 'project' => 'Cloud Aurora Tech',     'ago' => '-10 days',  'terms' => 'À réception'],
            ['ti' => 11, 'number' => 'FAC-2026-0006', 'status' => InvoiceStatusEnum::Validated,   'label' => 'Adobe CC — 5 licences annuelles',               'net' => 31500,  'gross' => 37800, 'project' => 'Studio créatif',         'ago' => '-7 days',   'terms' => 'Annuel'],
            ['ti' => 8,  'number' => 'FAC-2026-0007', 'status' => InvoiceStatusEnum::NeedsReview, 'label' => 'Prestation design UI — Refonte charte Q4',      'net' => 18000,  'gross' => 21600, 'project' => 'Refonte Marque 2025',   'ago' => '-5 days',   'terms' => '30 jours fin de mois'],
            ['ti' => 9,  'number' => null,             'status' => InvoiceStatusEnum::Draft,       'label' => 'Dev front-end Aurora — Sprint 8',               'net' => 9600,   'gross' => 11520, 'project' => 'Aurora v2.1',            'ago' => '-2 days',   'terms' => '15 jours'],
            ['ti' => 12, 'number' => null,             'status' => InvoiceStatusEnum::Draft,       'label' => 'Analyse données — Dashboard Q3 2025',           'net' => 7200,   'gross' => 8640,  'project' => 'BI & Analytics',         'ago' => '-1 day',    'terms' => '30 jours net'],
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
            if (null !== $ei['number']) {
                $inv->setNumber($ei['number']);
            }

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
        // ── Tags ──────────────────────────────────────────────────────────────
        $tagDefs = [
            ['name' => 'Confidentiel',  'color' => '#ef4444'],
            ['name' => 'À valider',     'color' => '#f59e0b'],
            ['name' => 'Signé',         'color' => '#10b981'],
            ['name' => 'Archivé',       'color' => '#6b7280'],
            ['name' => 'RGPD',          'color' => '#3b82f6'],
            ['name' => 'ISO 27001',     'color' => '#8b5cf6'],
        ];
        $tags = [];
        foreach ($tagDefs as $def) {
            $tag = new DocumentTag();
            $tag->setName($def['name'])->setColor($def['color']);
            $em->persist($tag);
            $tags[] = $tag;
        }

        // aliases: 0=Confidentiel, 1=À valider, 2=Signé, 3=Archivé, 4=RGPD, 5=ISO 27001

        // ── Folders ───────────────────────────────────────────────────────────
        $folderDefs = [
            ['name' => 'Aurora Tech',    'parent' => null, 'position' => 0],
            ['name' => 'Clients',        'parent' => null, 'position' => 1],
            ['name' => 'Internes',       'parent' => null, 'position' => 2],
            ['name' => 'Contrats',       'parent' => 1,    'position' => 0],
            ['name' => 'Présentations',  'parent' => 1,    'position' => 1],
            ['name' => 'RH',             'parent' => 2,    'position' => 0],
            ['name' => 'Finance',        'parent' => 2,    'position' => 1],
        ];
        $folders = [];
        foreach ($folderDefs as $def) {
            $folder = new DocumentFolder();
            $folder->setName($def['name'])->setPosition($def['position']);
            if (null !== $def['parent']) {
                $folder->setParent($folders[$def['parent']]);
            }

            $em->persist($folder);
            $folders[] = $folder;
        }

        // aliases: 0=Aurora Tech, 1=Clients, 2=Internes, 3=Contrats, 4=Présentations, 5=RH, 6=Finance

        // ── Categories ────────────────────────────────────────────────────────
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

        // ── Documents (cat, folder, tags) ─────────────────────────────────────
        $docDefs = [
            // title                                                          cat  folder  tags        status                              desc                                                                                       file
            ['title' => 'Contrat Tech Innovation SARL 2025',           'cat' => 0, 'folder' => 3, 'tags' => [0, 2],    'status' => DocumentStatusEnum::Published, 'desc' => 'Contrat de prestation de services signé le 15 janvier 2025. Durée : 12 mois renouvelable.', 'file' => 5],
            ['title' => 'Contrat BioMed France — Maintenance 2025',    'cat' => 0, 'folder' => 3, 'tags' => [0, 2],    'status' => DocumentStatusEnum::Published, 'desc' => 'Contrat de maintenance et support niveau 2 pour la suite Aurora.', 'file' => null],
            ['title' => 'Avenant Contrat Retail Connect — Jan 2025',   'cat' => 0, 'folder' => 3, 'tags' => [0, 1],    'status' => DocumentStatusEnum::Draft,     'desc' => 'Avenant tarifaire en cours de négociation pour le renouvellement 2025.', 'file' => null],
            ['title' => "Guide d'installation Aurora v2.0",            'cat' => 1, 'folder' => 0, 'tags' => [],         'status' => DocumentStatusEnum::Published, 'desc' => 'Documentation complète pour installer et configurer Aurora en production.', 'file' => null],
            ['title' => 'API Aurora — Documentation Développeur v2.1', 'cat' => 1, 'folder' => 0, 'tags' => [],         'status' => DocumentStatusEnum::Published, 'desc' => 'Référence complète de l\'API REST Aurora : endpoints, authentification, exemples.', 'file' => null],
            ['title' => 'Architecture Technique Aurora — Whitepaper',  'cat' => 1, 'folder' => 0, 'tags' => [3],        'status' => DocumentStatusEnum::Archived,  'desc' => 'Document d\'architecture technique v1.x (archivé, remplacé par la version 2.x).', 'file' => null],
            ['title' => 'Rapport Annuel 2024 — Aurora Tech',           'cat' => 4, 'folder' => 6, 'tags' => [0, 1],    'status' => DocumentStatusEnum::Draft,     'desc' => 'Bilan financier et opérationnel de l\'exercice 2024. En cours de validation.', 'file' => null],
            ['title' => 'Budget Prévisionnel 2025 — Aurora Tech',      'cat' => 4, 'folder' => 6, 'tags' => [0],        'status' => DocumentStatusEnum::Published, 'desc' => 'Budget prévisionnel approuvé par le comité de direction le 10 janvier 2025.', 'file' => null],
            ['title' => 'Charte Graphique Aurora — Brand Guidelines',  'cat' => 2, 'folder' => 4, 'tags' => [],         'status' => DocumentStatusEnum::Published, 'desc' => 'Couleurs, typographies, logos et règles d\'utilisation de la marque Aurora.', 'file' => 0],
            ['title' => 'Kit Presse Aurora Tech Day 2025',             'cat' => 2, 'folder' => 4, 'tags' => [],         'status' => DocumentStatusEnum::Published, 'desc' => 'Communiqué de presse, visuels HD et biographies intervenants.', 'file' => null],
            ['title' => 'Fiche de Poste — Développeur Full Stack',     'cat' => 3, 'folder' => 5, 'tags' => [4],        'status' => DocumentStatusEnum::Published, 'desc' => 'Description du poste, compétences requises et processus de recrutement.', 'file' => null],
            ['title' => 'Politique de Télétravail — Aurora Tech',      'cat' => 3, 'folder' => 5, 'tags' => [4],        'status' => DocumentStatusEnum::Published, 'desc' => 'Règles et procédures applicables au travail à distance.', 'file' => null],
            ['title' => 'Certification ISO 27001 — Audit 2024',        'cat' => 5, 'folder' => 2, 'tags' => [5, 0],    'status' => DocumentStatusEnum::Published, 'desc' => 'Rapport d\'audit de conformité ISO 27001 réalisé en novembre 2024.', 'file' => null],
        ];
        foreach ($docDefs as $def) {
            $d = new Document();
            $d->setTitle($def['title'])
              ->setDescription($def['desc'])
              ->setStatus($def['status'])
              ->setCategory($categories[$def['cat']])
              ->setFolder($folders[$def['folder']]);
            foreach ($def['tags'] as $tagIndex) {
                $d->addTag($tags[$tagIndex]);
            }

            if (null !== $def['file'] && isset($media[$def['file']])) {
                $d->setFile($media[$def['file']]);
            }

            $em->persist($d);
        }
    }

    // ── PDF Forms ─────────────────────────────────────────────────────────────

    private function createPdfForm(EntityManagerInterface $em): void
    {
        $month = new DateTimeImmutable()->format('Y/m');
        $destDir = $this->uploadDir.'/media/'.$month;
        $this->fs->mkdir($destDir);

        $src = dirname(__DIR__, 3).'/test_files/files/pdfs/pdfform_sample.pdf';
        $dest = $destDir.'/pdfform-sample.pdf';

        if (file_exists($src)) {
            $this->fs->copy($src, $dest, true);

            $m = new Media();
            $m->setFilename('pdfform-sample.pdf')
              ->setOriginalName('PDF Form.pdf')
              ->setMimeType('application/pdf')
              ->setSize((int) filesize($dest))
              ->setPath('media/'.$month.'/pdfform-sample.pdf')
              ->setVariants([]);
            $em->persist($m);
        }

        $em->flush();
    }

    // ── HR ────────────────────────────────────────────────────────────────────

    /** @param User[] $users */
    private function createHr(EntityManagerInterface $em, array $users): void
    {
        $agencies = $em->getRepository(Agency::class)->findAll();
        $services = $em->getRepository(Service::class)->findAll();

        $defs = [
            ['firstName' => 'Sophie',    'lastName' => 'Martin',    'title' => 'Responsable RH',              'email' => 'sophie.martin@aurora-tech.fr',    'phone' => '+33 6 10 11 12 13', 'hired' => '2019-03-01', 'left' => null,         'svc' => 'Ressources Humaines', 'agc' => 'Siège Social'],
            ['firstName' => 'Thomas',    'lastName' => 'Dubois',    'title' => 'Développeur Senior PHP',       'email' => 'thomas.dubois@aurora-tech.fr',    'phone' => '+33 6 20 21 22 23', 'hired' => '2020-06-15', 'left' => null,         'svc' => 'Développement',       'agc' => 'Agence Nord'],
            ['firstName' => 'Clara',     'lastName' => 'Lefèvre',   'title' => 'Chef de projet technique',     'email' => 'clara.lefevre@aurora-tech.fr',    'phone' => '+33 6 30 31 32 33', 'hired' => '2018-09-01', 'left' => null,         'svc' => 'Développement',       'agc' => 'Agence Nord'],
            ['firstName' => 'Julien',    'lastName' => 'Moreau',    'title' => 'Commercial Grands Comptes',    'email' => 'julien.moreau@aurora-tech.fr',    'phone' => '+33 6 40 41 42 43', 'hired' => '2021-01-10', 'left' => null,         'svc' => 'Commercial',          'agc' => 'Agence Sud'],
            ['firstName' => 'Camille',   'lastName' => 'Bernard',   'title' => 'Chargée de communication',    'email' => 'camille.bernard@aurora-tech.fr',  'phone' => '+33 6 50 51 52 53', 'hired' => '2022-04-04', 'left' => null,         'svc' => 'Marketing',           'agc' => 'Siège Social'],
            ['firstName' => 'Antoine',   'lastName' => 'Petit',     'title' => 'Développeur Full-Stack',       'email' => 'antoine.petit@aurora-tech.fr',    'phone' => '+33 6 60 61 62 63', 'hired' => '2021-09-20', 'left' => null,         'svc' => 'Développement',       'agc' => 'Agence Est'],
            ['firstName' => 'Lucie',     'lastName' => 'Girard',    'title' => 'Assistante de direction',      'email' => 'lucie.girard@aurora-tech.fr',     'phone' => '+33 6 70 71 72 73', 'hired' => '2017-11-15', 'left' => null,         'svc' => 'Direction',           'agc' => 'Siège Social'],
            ['firstName' => 'Marc',      'lastName' => 'Rousseau',  'title' => 'Ingénieur DevOps',             'email' => 'marc.rousseau@aurora-tech.fr',    'phone' => '+33 6 80 81 82 83', 'hired' => '2020-02-01', 'left' => null,         'svc' => 'Développement',       'agc' => 'Agence Ouest'],
            ['firstName' => 'Emma',      'lastName' => 'Dupont',    'title' => 'Designer UX/UI',               'email' => 'emma.dupont@aurora-tech.fr',      'phone' => '+33 6 90 91 92 93', 'hired' => '2022-07-18', 'left' => null,         'svc' => 'Marketing',           'agc' => 'Siège Social'],
            ['firstName' => 'Nicolas',   'lastName' => 'Lambert',   'title' => 'Responsable Commercial',       'email' => 'nicolas.lambert@aurora-tech.fr',  'phone' => '+33 7 00 01 02 03', 'hired' => '2016-05-09', 'left' => null,         'svc' => 'Commercial',          'agc' => 'Agence Sud'],
            ['firstName' => 'Inès',      'lastName' => 'Fontaine',  'title' => 'Développeuse Backend',         'email' => 'ines.fontaine@aurora-tech.fr',    'phone' => '+33 7 10 11 12 13', 'hired' => '2023-01-09', 'left' => null,         'svc' => 'Développement',       'agc' => 'Agence Nord'],
            ['firstName' => 'Romain',    'lastName' => 'Garnier',   'title' => 'Consultant technique',         'email' => 'romain.garnier@aurora-tech.fr',   'phone' => '+33 7 20 21 22 23', 'hired' => '2019-08-26', 'left' => '2024-06-30', 'svc' => 'Développement',       'agc' => 'Agence Est'],
            ['firstName' => 'Mathilde',  'lastName' => 'Chevalier', 'title' => 'Chargée de recrutement',       'email' => 'mathilde.chevalier@aurora-tech.fr', 'phone' => '+33 7 30 31 32 33', 'hired' => '2023-09-04', 'left' => null,         'svc' => 'Ressources Humaines', 'agc' => 'Siège Social'],
            ['firstName' => 'Pierre',    'lastName' => 'Morel',     'title' => 'Directeur Technique',          'email' => 'pierre.morel@aurora-tech.fr',     'phone' => '+33 7 40 41 42 43', 'hired' => '2015-01-05', 'left' => null,         'svc' => 'Direction',           'agc' => 'Siège Social'],
            ['firstName' => 'Laura',     'lastName' => 'Simon',     'title' => 'Développeuse Mobile',          'email' => 'laura.simon@aurora-tech.fr',      'phone' => '+33 7 50 51 52 53', 'hired' => '2024-03-11', 'left' => null,         'svc' => 'Développement',       'agc' => 'Agence Ouest'],
        ];

        $agencyMap = [];
        foreach ($agencies as $a) {
            $agencyMap[$a->getName()] = $a;
        }

        $serviceMap = [];
        foreach ($services as $s) {
            $serviceMap[$s->getName()] = $s;
        }

        foreach ($defs as $i => $def) {
            $employee = new Employee();
            $employee->setFirstName($def['firstName'])
                     ->setLastName($def['lastName'])
                     ->setJobTitle($def['title'])
                     ->setWorkEmail($def['email'])
                     ->setPhone($def['phone'])
                     ->setHiredAt(new DateTimeImmutable($def['hired']))
                     ->setLeftAt(null !== $def['left'] ? new DateTimeImmutable($def['left']) : null)
                     ->setUser($users[$i] ?? null)
                     ->setService($serviceMap[$def['svc']] ?? null)
                     ->setAgency($agencyMap[$def['agc']] ?? null);
            $em->persist($employee);
        }

        $em->flush();
    }

    // ── Planning ──────────────────────────────────────────────────────────────

    /** @param User[] $users */
    private function createPlanning(EntityManagerInterface $em, array $users): void
    {
        $admin = $users[0] ?? null;
        $now = new DateTimeImmutable();

        // Planning 1 — Équipe Développement (partagé agence)
        $p1 = new Planning();
        $p1->setName('Équipe Développement')
           ->setDescription('Planning de l\'équipe dev : sprints, revues, daily stand-ups.')
           ->setColor('#3b82f6')
           ->setTimezone('Europe/Paris')
           ->setVisibility(PlanningVisibilityEnum::Agency)
           ->setOwner($admin);
        $em->persist($p1);

        // Planning 2 — Direction (privé)
        $p2 = new Planning();
        $p2->setName('Comités de direction')
           ->setDescription('Réunions CODIR, board et revues stratégiques.')
           ->setColor('#8b5cf6')
           ->setTimezone('Europe/Paris')
           ->setVisibility(PlanningVisibilityEnum::Private_)
           ->setOwner($admin);
        $em->persist($p2);

        // Planning 3 — Congés & absences (public)
        $p3 = new Planning();
        $p3->setName('Congés & absences')
           ->setDescription('Suivi des congés payés, RTT et absences exceptionnelles.')
           ->setColor('#10b981')
           ->setTimezone('Europe/Paris')
           ->setVisibility(PlanningVisibilityEnum::Public_)
           ->setOwner($admin);
        $em->persist($p3);

        // Planning 4 — Commercial
        $p4 = new Planning();
        $p4->setName('Agenda Commercial')
           ->setDescription('Rendez-vous clients, démos produit et appels de suivi.')
           ->setColor('#f59e0b')
           ->setTimezone('Europe/Paris')
           ->setVisibility(PlanningVisibilityEnum::Agency)
           ->setOwner($users[1] ?? $admin);
        $em->persist($p4);

        $addEvent = static function (EntityManagerInterface $em, Planning $planning, string $title, string $start, string $end, PlanningEventStatusEnum $status = PlanningEventStatusEnum::Confirmed, ?string $location = null, ?string $description = null, bool $allDay = false): void {
            $event = new PlanningEvent();
            $event->setPlanning($planning)
                  ->setTitle($title)
                  ->setStartAt(new DateTimeImmutable($start))
                  ->setEndAt(new DateTimeImmutable($end))
                  ->setStatus($status)
                  ->setLocation($location)
                  ->setDescription($description)
                  ->setAllDay($allDay);
            $em->persist($event);
        };

        // Événements — Développement
        $addEvent($em, $p1, 'Daily stand-up', 'today 09:00', 'today 09:15', PlanningEventStatusEnum::Confirmed, 'Visio', 'Réunion quotidienne d\'avancement.');
        $addEvent($em, $p1, 'Sprint review S24', 'this monday 14:00', 'this monday 16:00', PlanningEventStatusEnum::Confirmed, 'Salle Arctique', 'Démonstration des fonctionnalités livrées ce sprint.');
        $addEvent($em, $p1, 'Sprint planning S25', 'next monday 10:00', 'next monday 12:00', PlanningEventStatusEnum::Confirmed, 'Salle Arctique', 'Planification et estimation du prochain sprint.');
        $addEvent($em, $p1, 'Atelier architecture API', $now->modify('+3 days')->format('Y-m-d').' 09:30', $now->modify('+3 days')->format('Y-m-d').' 12:00', PlanningEventStatusEnum::Confirmed, 'Salle Boréale', 'Revue de la conception des nouveaux endpoints REST.');
        $addEvent($em, $p1, 'Mise en production v2.4', $now->modify('+7 days')->format('Y-m-d').' 22:00', $now->modify('+8 days')->format('Y-m-d').' 01:00', PlanningEventStatusEnum::Tentative, null, 'Déploiement Aurora v2.4 en production. Fenêtre de maintenance.');
        $addEvent($em, $p1, 'Formation Docker interne', $now->modify('-5 days')->format('Y-m-d').' 14:00', $now->modify('-5 days')->format('Y-m-d').' 17:00', PlanningEventStatusEnum::Confirmed, 'Salle Boréale', 'Formation containerisation pour l\'équipe dev.');
        $addEvent($em, $p1, 'Rétrospective S23', $now->modify('-14 days')->format('Y-m-d').' 15:00', $now->modify('-14 days')->format('Y-m-d').' 16:30', PlanningEventStatusEnum::Confirmed, 'Visio', 'Bilan du sprint 23 — points positifs et axes d\'amélioration.');

        // Événements — Direction
        $addEvent($em, $p2, 'CODIR mensuel — '.date('F Y'), $now->modify('first day of this month')->format('Y-m-d').' 09:00', $now->modify('first day of this month')->format('Y-m-d').' 12:00', PlanningEventStatusEnum::Confirmed, 'Salle de direction', 'Revue des KPIs et décisions stratégiques du mois.');
        $addEvent($em, $p2, 'Revue budgétaire T2 2025', $now->modify('+10 days')->format('Y-m-d').' 10:00', $now->modify('+10 days')->format('Y-m-d').' 12:00', PlanningEventStatusEnum::Confirmed, 'Salle de direction', 'Analyse des dépenses et ajustements budgétaires T2.');
        $addEvent($em, $p2, 'Board Aurora — juin 2025', $now->modify('+21 days')->format('Y-m-d').' 09:00', $now->modify('+21 days')->format('Y-m-d').' 17:00', PlanningEventStatusEnum::Tentative, 'Paris — Siège', 'Réunion annuelle des actionnaires et présentation des résultats.');

        // Événements — Congés
        $addEvent($em, $p3, 'Congés Thomas Dubois', $now->modify('+14 days')->format('Y-m-d'), $now->modify('+21 days')->format('Y-m-d'), PlanningEventStatusEnum::Confirmed, null, null, true);
        $addEvent($em, $p3, 'Congés Clara Lefèvre', $now->modify('+28 days')->format('Y-m-d'), $now->modify('+35 days')->format('Y-m-d'), PlanningEventStatusEnum::Confirmed, null, null, true);
        $addEvent($em, $p3, 'RTT — Lucie Girard', $now->modify('+5 days')->format('Y-m-d'), $now->modify('+5 days')->format('Y-m-d'), PlanningEventStatusEnum::Confirmed, null, null, true);
        $addEvent($em, $p3, 'Arrêt maladie Marc Rousseau', $now->modify('-3 days')->format('Y-m-d'), $now->modify('+2 days')->format('Y-m-d'), PlanningEventStatusEnum::Confirmed, null, null, true);

        // Événements — Commercial
        $addEvent($em, $p4, 'Démo Aurora — BioMed France', $now->modify('+2 days')->format('Y-m-d').' 10:00', $now->modify('+2 days')->format('Y-m-d').' 11:30', PlanningEventStatusEnum::Confirmed, 'Visio Teams', 'Présentation des modules GED et Facturation à BioMed France.');
        $addEvent($em, $p4, 'Déjeuner client — Retail Connect', $now->modify('+4 days')->format('Y-m-d').' 12:30', $now->modify('+4 days')->format('Y-m-d').' 14:00', PlanningEventStatusEnum::Confirmed, 'Restaurant Le Procope, Paris', 'Suivi commercial et renouvellement contrat 2025.');
        $addEvent($em, $p4, 'Appel découverte — Novo Pharma', $now->modify('+6 days')->format('Y-m-d').' 15:00', $now->modify('+6 days')->format('Y-m-d').' 15:45', PlanningEventStatusEnum::Tentative, 'Téléphone', 'Premier contact — présentation Aurora et qualification du besoin.');
        $addEvent($em, $p4, 'Salon Tech Paris 2025', $now->modify('+30 days')->format('Y-m-d'), $now->modify('+32 days')->format('Y-m-d'), PlanningEventStatusEnum::Confirmed, 'Paris Expo Porte de Versailles', 'Présence Aurora au salon — stand B42.', true);
        $addEvent($em, $p4, 'Closing Tech Innovation SARL', $now->modify('-7 days')->format('Y-m-d').' 14:00', $now->modify('-7 days')->format('Y-m-d').' 15:00', PlanningEventStatusEnum::Confirmed, 'Visio', 'Signature finale du contrat de prestation 2025.');

        $em->flush();
    }

    // ── Projects ──────────────────────────────────────────────────────────────

    /**
     * @param User[]    $users
     * @param Company[] $companies
     * @param Contact[] $contacts
     */
    private function createProjects(EntityManagerInterface $em, array $users, array $companies, array $contacts): void
    {
        $projectDefs = [
            [
                'ref' => 'PRJ-000001',
                'title' => 'Refonte CRM Tech Innovation',
                'description' => 'Migration de l\'ancien CRM vers Aurora avec import des données existantes et formation de l\'équipe commerciale.',
                'status' => ProjectStatusEnum::Active,
                'startDate' => '-2 months',
                'endDate' => '+3 months',
                'responsible' => 1,
                'company' => 0,
                'contacts' => [0, 3, 14],
            ],
            [
                'ref' => 'PRJ-000002',
                'title' => 'Site web BioMed France',
                'description' => 'Refonte complète du site institutionnel avec section produits dynamique et espace pro.',
                'status' => ProjectStatusEnum::Active,
                'startDate' => '-3 weeks',
                'endDate' => '+2 months',
                'responsible' => 2,
                'company' => 1,
                'contacts' => [1],
            ],
            [
                'ref' => 'PRJ-000003',
                'title' => 'Solution e-commerce Retail Connect',
                'description' => 'Boutique en ligne B2B avec gestion des grilles tarifaires par client et intégration ERP.',
                'status' => ProjectStatusEnum::Draft,
                'startDate' => '+2 weeks',
                'endDate' => '+5 months',
                'responsible' => 1,
                'company' => 2,
                'contacts' => [2],
            ],
            [
                'ref' => 'PRJ-000004',
                'title' => 'Audit infrastructure Nexus Digital',
                'description' => 'Audit complet de l\'infrastructure cloud et recommandations d\'optimisation des coûts.',
                'status' => ProjectStatusEnum::Completed,
                'startDate' => '-4 months',
                'endDate' => '-1 month',
                'responsible' => 3,
                'company' => 3,
                'contacts' => [5],
            ],
            [
                'ref' => 'PRJ-000005',
                'title' => 'Déploiement GED Groupe Leclerc Nord',
                'description' => 'Mise en place de la GED Aurora avec catégorisation des documents et workflow d\'approbation.',
                'status' => ProjectStatusEnum::Active,
                'startDate' => '-1 month',
                'endDate' => '+6 weeks',
                'responsible' => 2,
                'company' => 4,
                'contacts' => [6],
            ],
            [
                'ref' => 'PRJ-000006',
                'title' => 'Migration Cloud BioMed (annulé)',
                'description' => 'Projet abandonné suite au changement de stratégie IT du client. Conservé pour historique.',
                'status' => ProjectStatusEnum::Cancelled,
                'startDate' => '-5 months',
                'endDate' => '-2 months',
                'responsible' => 1,
                'company' => 1,
                'contacts' => [1],
            ],
        ];

        $createdProjects = [];
        // Per-project columns indexed by status slug for easy lookup when seeding tasks.
        // Slugs: 'todo' / 'in_progress' / 'done' / 'cancelled' (last one only on the cancelled project for demo).
        $projectColumns = [];
        foreach ($projectDefs as $projectIndex => $def) {
            $project = new Project();
            $project->setReference($def['ref'])
                ->setTitle($def['title'])
                ->setDescription($def['description'])
                ->setStatus($def['status'])
                ->setStartDate(new DateTimeImmutable($def['startDate']))
                ->setEndDate(new DateTimeImmutable($def['endDate']))
                ->setResponsibleUser($users[$def['responsible']])
                ->setCrmCompany($companies[$def['company']]);
            foreach ($def['contacts'] as $contactIndex) {
                if (isset($contacts[$contactIndex])) {
                    $project->addCrmContact($contacts[$contactIndex]);
                }
            }

            $em->persist($project);
            $createdProjects[] = $project;

            $columnLabels = ['todo' => 'À faire', 'in_progress' => 'En cours', 'done' => 'Terminé'];
            // Add a custom 4th column on the cancelled project to showcase the feature.
            if (ProjectStatusEnum::Cancelled === $def['status']) {
                $columnLabels['cancelled'] = 'Annulé';
            }

            $columns = [];
            $position = 0;
            foreach ($columnLabels as $slug => $label) {
                $column = new ProjectColumn();
                $column->setProject($project)
                    ->setLabel($label)
                    ->setPosition($position++)
                    ->setReference(sprintf('PRJC-%06d', ($projectIndex * 10) + $position));
                $em->persist($column);
                $columns[$slug] = $column;
            }

            $projectColumns[$projectIndex] = $columns;
        }

        // Labels per project (index => [name => color])
        $labelDefs = [
            0 => ['Backend' => 'accent',  'Frontend' => 'sky',     'Data' => 'violet', 'Urgent' => 'rose'],
            1 => ['Design' => 'amber',   'Dev' => 'accent',  'Contenu' => 'emerald'],
            2 => ['Technique' => 'accent', 'Commercial' => 'emerald', 'Priorité' => 'rose'],
            3 => ['Infrastructure' => 'slate', 'Rapport' => 'sky'],
            4 => ['Configuration' => 'accent', 'Formation' => 'emerald', 'Import' => 'amber'],
            5 => ['Cloud' => 'sky', 'Annulé' => 'rose'],
        ];

        // $projectLabels[projectIndex][name] = ProjectLabel
        $projectLabels = [];
        foreach ($labelDefs as $projectIndex => $labels) {
            $project = $createdProjects[$projectIndex];
            foreach ($labels as $name => $color) {
                $label = new ProjectLabel();
                $label->setProject($project)->setName($name)->setColor($color);
                $em->persist($label);
                $projectLabels[$projectIndex][$name] = $label;
            }
        }

        // Sprints for active projects
        $sprintDefs = [
            0 => [
                ['name' => 'Sprint 1 — Analyse',   'start' => '-6 weeks', 'end' => '-3 weeks', 'active' => false],
                ['name' => 'Sprint 2 — Migration',  'start' => '-3 weeks', 'end' => '+1 week',  'active' => true],
            ],
            1 => [
                ['name' => 'Sprint 1 — Design',     'start' => '-2 weeks', 'end' => '+2 weeks', 'active' => true],
            ],
            4 => [
                ['name' => 'Sprint 1 — Setup',      'start' => '-1 month', 'end' => '+2 weeks', 'active' => true],
            ],
        ];

        // $projectSprints[projectIndex][name] = ProjectSprint
        $projectSprints = [];
        foreach ($sprintDefs as $projectIndex => $sprints) {
            $project = $createdProjects[$projectIndex];
            foreach ($sprints as $sprintDef) {
                $sprint = new ProjectSprint();
                $sprint->setProject($project)
                    ->setName($sprintDef['name'])
                    ->setStartDate(new DateTimeImmutable($sprintDef['start']))
                    ->setEndDate(new DateTimeImmutable($sprintDef['end']))
                    ->setIsActive($sprintDef['active']);
                $em->persist($sprint);
                $projectSprints[$projectIndex][$sprintDef['name']] = $sprint;
            }
        }

        // Tasks per project: index → list of task definitions.
        // 'labels' = label names to attach, 'sprint' = sprint name to attach (optional)
        $taskDefs = [
            // PRJ-000001 — Refonte CRM (Active, en pleine progression)
            0 => [
                ['title' => 'Cadrage des besoins métier',       'column' => 'done',        'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 1, 'due' => '-6 weeks',  'labels' => ['Backend', 'Data'],     'sprint' => 'Sprint 1 — Analyse',   'description' => "Organiser et animer les ateliers métier avec les référents commerciaux et support.\n\nObjectifs :\n- Recenser les processus existants dans l'ancien CRM\n- Identifier les données critiques à conserver\n- Lister les fonctionnalités indispensables pour le go-live\n\nLivrable : compte-rendu d'atelier validé par le chef de projet client."],
                ['title' => 'Maquettes interfaces principales', 'column' => 'done',        'priority' => ProjectTaskPriorityEnum::Medium, 'assignee' => 2, 'due' => '-4 weeks',  'labels' => ['Frontend'],            'sprint' => 'Sprint 1 — Analyse',   'description' => "Concevoir les maquettes Figma pour les écrans principaux : tableau de bord, fiche contact, pipeline commercial.\n\nContraintes :\n- Respecter la charte graphique Aurora\n- Prévoir une version responsive mobile\n\nValidation attendue par le client avant intégration."],
                ['title' => 'Import données ancien CRM',        'column' => 'in_progress', 'priority' => ProjectTaskPriorityEnum::Urgent, 'assignee' => 1, 'due' => '+1 week',   'labels' => ['Data', 'Urgent'],      'sprint' => 'Sprint 2 — Migration', 'description' => "Exporter et importer les données depuis l'ancien CRM (Salesforce v1) vers Aurora.\n\nPoints de vigilance :\n- ~45 000 contacts à migrer\n- Dédoublonnage obligatoire avant import\n- Champs custom à mapper manuellement (voir tâche liée)\n\n⚠️ Date butoir impérative : la licence Salesforce expire dans 10 jours."],
                ['title' => 'Mapping champs personnalisés',     'column' => 'in_progress', 'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 2, 'due' => '+10 days',  'labels' => ['Backend', 'Data'],     'sprint' => 'Sprint 2 — Migration', 'description' => "Établir la table de correspondance entre les champs custom Salesforce et le schéma Aurora.\n\nChamps identifiés à traiter :\n- `sf_segment_client` → `tag:segment`\n- `sf_ca_annuel` → `meta:revenue_annual`\n- `sf_date_derniere_relance` → `activity:last_contact`\n\nLivrable : fichier de mapping validé par le responsable data."],
                ['title' => 'Formation utilisateurs clés',      'column' => 'todo',        'priority' => ProjectTaskPriorityEnum::Medium, 'assignee' => 3, 'due' => '+1 month',  'labels' => ['Frontend'],            'sprint' => null,                   'description' => "Former les 8 utilisateurs référents sur Aurora CRM avant le déploiement général.\n\nProgramme :\n1. Prise en main de l'interface (2h)\n2. Gestion des contacts et opportunités (2h)\n3. Tableaux de bord et rapports (1h)\n\nSupports à préparer : guide utilisateur PDF + vidéos tuto courtes."],
                ['title' => 'Recette finale + go-live',         'column' => 'todo',        'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 1, 'due' => '+2 months', 'labels' => ['Backend', 'Urgent'],   'sprint' => null,                   'description' => "Valider l'ensemble du périmètre fonctionnel avant ouverture en production.\n\nChecklist recette :\n- [ ] Import données complet et vérifié\n- [ ] Droits et rôles utilisateurs configurés\n- [ ] Intégrations tierces opérationnelles (messagerie, agenda)\n- [ ] Backups automatiques activés\n\nGo/no-go décidé en réunion de pilotage avec le client."],
            ],
            // PRJ-000002 — Site BioMed (démarrage)
            1 => [
                ['title' => 'Recueil charte graphique client',  'column' => 'done',        'priority' => ProjectTaskPriorityEnum::Medium, 'assignee' => 2, 'due' => '-2 weeks',  'labels' => ['Design'],              'sprint' => 'Sprint 1 — Design',    'description' => "Collecter tous les éléments de la charte graphique BioMed fournis par le client.\n\nÉléments reçus :\n- Logo en SVG et PNG (fond blanc + fond sombre)\n- Palette couleurs : bleu marine `#0A2342`, vert menthe `#3FBFA0`, blanc `#FFFFFF`\n- Typographies : Montserrat (titres), Open Sans (corps)\n- Iconographie : style outline, stroke 1.5px\n\nTout est archivé dans le dossier partagé Google Drive."],
                ['title' => 'Wireframes pages clés',            'column' => 'in_progress', 'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 2, 'due' => '+5 days',   'labels' => ['Design'],              'sprint' => 'Sprint 1 — Design',    'description' => "Créer les wireframes basse fidélité des pages stratégiques du site.\n\nPages à traiter :\n- Accueil\n- Page produit / solution\n- Espace professionnel (accès restreint)\n- Contact\n\nOutil : Figma — partager le lien en commentaire une fois les maquettes prêtes pour review client."],
                ['title' => 'Intégration HTML/CSS homepage',    'column' => 'todo',        'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 3, 'due' => '+3 weeks',  'labels' => ['Dev'],                 'sprint' => null,                   'description' => "Intégrer la page d'accueil à partir des maquettes validées en sprint 1.\n\nSpécifications techniques :\n- Framework : Tailwind CSS v3\n- Responsive : mobile-first (breakpoints sm/md/lg)\n- Animations : transitions CSS uniquement, pas de JS lourd\n- Accessibilité : WCAG AA minimum\n\nTests sur Chrome, Firefox, Safari (desktop + mobile)."],
                ['title' => 'Section catalogue produits',       'column' => 'todo',        'priority' => ProjectTaskPriorityEnum::Medium, 'assignee' => 3, 'due' => '+5 weeks',  'labels' => ['Dev', 'Contenu'],      'sprint' => null,                   'description' => "Développer la section catalogue avec liste et pages détail produit.\n\nFonctionnalités :\n- Filtres par catégorie et indication\n- Fiche produit avec téléchargement de notice PDF\n- Formulaire de demande d'information (sans prix — marché pro uniquement)\n\nLe contenu produit (textes, images) sera fourni par le client sous 2 semaines."],
                ['title' => 'Mise en ligne staging',            'column' => 'todo',        'priority' => ProjectTaskPriorityEnum::Low,    'assignee' => 1, 'due' => '+7 weeks',  'labels' => ['Dev'],                 'sprint' => null,                   'description' => "Déployer le site sur l'environnement de staging pour validation client.\n\nÉtapes :\n1. Build de prod et optimisation assets\n2. Déploiement sur staging.biomed-aurora.fr\n3. Smoke tests (pages principales, formulaires, redirections)\n4. Envoi du lien au client avec accès HTTP basic auth\n\nRetours client attendus sous 5 jours ouvrés."],
            ],
            // PRJ-000003 — E-commerce Retail Connect (kickoff)
            2 => [
                ['title' => 'Atelier de cadrage avec le client', 'column' => 'todo',       'priority' => ProjectTaskPriorityEnum::Urgent, 'assignee' => 1, 'due' => '+2 weeks',  'labels' => ['Commercial', 'Priorité'], 'sprint' => null, 'description' => "Réunion de lancement avec les décideurs Retail Connect pour aligner vision et périmètre.\n\nParticipants côté client : DSI, Directeur Commercial, Chef de projet e-commerce.\n\nPoints à aborder :\n- Vision produit et objectifs business\n- Contraintes techniques (ERP existant, SI en place)\n- Budget et planning cible\n- Gouvernance du projet\n\nDurée estimée : demi-journée. À planifier en salle ou en visio."],
                ['title' => 'Spécifications fonctionnelles',    'column' => 'todo',        'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 2, 'due' => '+1 month',  'labels' => ['Technique'],           'sprint' => null, 'description' => "Rédiger le document de spécifications fonctionnelles détaillées de la plateforme e-commerce.\n\nChapitres principaux :\n1. Gestion du catalogue (produits, variantes, stocks)\n2. Grilles tarifaires par segment client (B2B)\n3. Tunnel d'achat et modes de paiement\n4. Intégration ERP (flux commandes, stock temps réel)\n5. Back-office et gestion des comptes clients\n\nDocument à soumettre pour relecture et validation avant démarrage du développement."],
                ['title' => 'Devis détaillé',                   'column' => 'todo',        'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 1, 'due' => '+3 weeks',  'labels' => ['Commercial'],          'sprint' => null, 'description' => "Établir le devis complet du projet sur la base du cadrage et des spécifications préliminaires.\n\nDécomposition attendue :\n- Design UX/UI\n- Développement front & back\n- Intégrations tierces (ERP, paiement, logistique)\n- Recette et déploiement\n- Maintenance année 1\n\nDevis à envoyer sous format PDF signé électroniquement. Délai de validation client : 10 jours."],
            ],
            // PRJ-000004 — Audit Nexus (terminé)
            3 => [
                ['title' => 'Inventaire ressources cloud',      'column' => 'done',        'priority' => ProjectTaskPriorityEnum::Medium, 'assignee' => 3, 'due' => '-3 months', 'labels' => ['Infrastructure'],      'sprint' => null, 'description' => "Recenser l'ensemble des ressources cloud actives dans les comptes AWS et Azure de Nexus.\n\nPérimètre audité :\n- 3 comptes AWS (prod, staging, dev)\n- 1 tenant Azure (Active Directory + quelques VMs)\n\nOutils utilisés : AWS Cost Explorer, Infracost, script maison d'inventaire via CLI.\nRésultat : ~340 ressources identifiées, dont 60 non taggées et 25 orphelines."],
                ['title' => 'Analyse de la facturation 12 mois', 'column' => 'done',        'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 3, 'due' => '-2 months', 'labels' => ['Infrastructure'],      'sprint' => null, 'description' => "Analyser les factures cloud des 12 derniers mois pour identifier les postes de dépenses anormaux.\n\nConclusions principales :\n- Transferts de données sortants : +40% par rapport au secteur (mauvaise configuration CloudFront)\n- Instances EC2 surdimensionnées : 12 instances t3.xlarge tournant à <10% CPU\n- Snapshots orphelins : 180 Go de stockage inutile à ~22€/mois\n\nÉconomies potentielles estimées : 2 800 €/mois."],
                ['title' => 'Rapport final + recommandations',  'column' => 'done',        'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 1, 'due' => '-6 weeks',  'labels' => ['Rapport'],             'sprint' => null, 'description' => "Rédiger le rapport d'audit complet avec plan d'action priorisé.\n\nStructure du rapport :\n1. Synthèse exécutive (2 pages)\n2. État des lieux détaillé par service cloud\n3. Matrice risques / optimisations\n4. Plan d'action 90 jours (quick wins vs chantiers lourds)\n5. Projection d'économies sur 12 mois\n\nDocument livré en PDF et présenté au COMEX Nexus."],
                ['title' => 'Restitution au COMEX',             'column' => 'done',        'priority' => ProjectTaskPriorityEnum::Medium, 'assignee' => 1, 'due' => '-1 month',  'labels' => ['Rapport'],             'sprint' => null, 'description' => "Présenter les conclusions de l'audit au Comité Exécutif de Nexus.\n\nFormat : présentation Keynote de 20 slides, session de 45 min + 15 min questions.\n\nPoints forts mis en avant :\n- ROI de l'audit : économies estimées x8 le coût de la prestation\n- 3 quick wins actionnables en moins d'une semaine\n- Recommandation d'un FinOps mensuel récurrent\n\nDécision COMEX : validation du plan d'action, démarrage dès la semaine suivante."],
            ],
            // PRJ-000005 — GED Leclerc (en cours)
            4 => [
                ['title' => 'Définition de l\'arborescence',   'column' => 'done',        'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 2, 'due' => '-2 weeks',  'labels' => ['Configuration'],       'sprint' => 'Sprint 1 — Setup',     'description' => "Co-construire avec les équipes Leclerc l'arborescence documentaire de la GED.\n\nArborescence retenue (4 niveaux) :\n- Direction / Pôle / Service / Type de document\n\nContraintes exprimées :\n- Max 3 clics pour accéder à n'importe quel document\n- Nomenclature normalisée obligatoire (ex: `2025-05_CONTRAT_Fournisseur-X.pdf`)\n\nValidé par la DSI et la Direction Achats lors de l'atelier du 22 avril."],
                ['title' => 'Configuration des catégories',    'column' => 'in_progress', 'priority' => ProjectTaskPriorityEnum::Medium, 'assignee' => 2, 'due' => '+5 days',   'labels' => ['Configuration'],       'sprint' => 'Sprint 1 — Setup',     'description' => "Paramétrer les catégories, métadonnées et droits d'accès dans Aurora GED.\n\nEn cours :\n- Création des 12 catégories de niveau 1\n- Configuration des champs métadonnées par type de document\n- Attribution des rôles (lecture seule / contributeur / validateur)\n\nBloquant identifié : la liste des référents par service n'a pas encore été transmise par les RH Leclerc."],
                ['title' => 'Import documents existants',      'column' => 'todo',        'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 3, 'due' => '+2 weeks',  'labels' => ['Import'],              'sprint' => 'Sprint 1 — Setup',     'description' => "Importer le stock documentaire existant (serveur de fichiers Windows) vers Aurora GED.\n\nVolume estimé : ~18 000 fichiers, 120 Go.\n\nMéthodologie :\n1. Nettoyage préalable (doublons, fichiers obsolètes >5 ans)\n2. Renommage selon la nomenclature validée\n3. Import par lot via le connecteur Aurora CLI\n4. Vérification d'intégrité sur un échantillon de 500 fichiers\n\nAccès VPN au serveur Leclerc à demander à la DSI."],
                ['title' => 'Workflow approbation',            'column' => 'todo',        'priority' => ProjectTaskPriorityEnum::Medium, 'assignee' => 1, 'due' => '+4 weeks',  'labels' => ['Configuration'],       'sprint' => null,                   'description' => "Configurer les circuits d'approbation documentaire pour les types de documents soumis à validation.\n\nCircuits à paramétrer :\n- Contrats fournisseurs : Responsable Achats → Directeur Achats → DAF\n- Notes de frais : Manager direct → Comptabilité\n- Procédures qualité : Rédacteur → Responsable Qualité\n\nTests à réaliser avec les valideurs désignés avant mise en production."],
                ['title' => 'Formation équipe achats',         'column' => 'todo',        'priority' => ProjectTaskPriorityEnum::Low,    'assignee' => 2, 'due' => '+5 weeks',  'labels' => ['Formation'],           'sprint' => null,                   'description' => "Former les 15 collaborateurs du service Achats à l'utilisation quotidienne de la GED Aurora.\n\nContenu de la formation (3h) :\n- Déposer et rechercher un document\n- Utiliser les métadonnées et filtres\n- Suivre et traiter les workflows d'approbation\n- Bonnes pratiques de nommage\n\nSupport : guide utilisateur illustré + QR code vers les vidéos tuto hébergées en interne."],
            ],
            // PRJ-000006 — Migration Cloud BioMed (annulé)
            5 => [
                ['title' => 'Étude technique préliminaire',    'column' => 'done',        'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 3, 'due' => '-4 months', 'labels' => ['Cloud'],               'sprint' => null, 'description' => "Analyser l'infrastructure on-premise BioMed et évaluer la faisabilité d'une migration vers AWS.\n\nInfrastructure auditée :\n- 8 serveurs physiques (dont 2 legacy Windows Server 2008)\n- Base de données Oracle 11g (critique, peu documentée)\n- Réseau privé avec contraintes réglementaires (données de santé — HDS)\n\nConclusion : migration techniquement faisable mais coût élevé dû aux contraintes HDS et à la dette technique Oracle."],
                ['title' => 'Devis migration AWS',             'column' => 'cancelled',   'priority' => ProjectTaskPriorityEnum::Medium, 'assignee' => 1, 'due' => '-3 months', 'labels' => ['Cloud', 'Annulé'],     'sprint' => null, 'description' => "Établir le devis de la migration complète vers AWS en tenant compte des exigences HDS.\n\n⚠️ Tâche annulée — le projet a été abandonné avant validation de ce devis.\n\nRaison : lors de la présentation du budget prévisionnel (380 k€ sur 18 mois), la direction BioMed a décidé de geler le projet et de réévaluer la stratégie IT à horizon 2026."],
                ['title' => 'Plan de bascule',                 'column' => 'cancelled',   'priority' => ProjectTaskPriorityEnum::High,   'assignee' => 3, 'due' => '-2 months', 'labels' => ['Annulé'],              'sprint' => null, 'description' => "Rédiger le plan de migration détaillé (cut-over plan) pour la bascule progressive vers AWS.\n\n⚠️ Tâche annulée suite à l'arrêt du projet.\n\nCe livrable n'a pas été produit. Les éléments de l'étude préliminaire sont conservés dans le dossier projet pour une éventuelle reprise ultérieure."],
            ],
        ];

        foreach ($taskDefs as $projectIndex => $tasks) {
            $project = $createdProjects[$projectIndex];
            $columnsBySlug = $projectColumns[$projectIndex];
            foreach ($tasks as $position => $taskDef) {
                $column = $columnsBySlug[$taskDef['column']] ?? $columnsBySlug['done'];

                $task = new ProjectTask();
                $task->setProject($project)
                    ->setColumn($column)
                    ->setReference(sprintf('TSK-%06d', ($projectIndex * 100) + $position + 1))
                    ->setTitle($taskDef['title'])
                    ->setDescription($taskDef['description'])
                    ->setPriority($taskDef['priority'])
                    ->setPosition($position)
                    ->setAssignee($users[$taskDef['assignee']])
                    ->setDueDate(new DateTimeImmutable($taskDef['due']));

                foreach ($taskDef['labels'] as $labelName) {
                    if (isset($projectLabels[$projectIndex][$labelName])) {
                        $task->addLabel($projectLabels[$projectIndex][$labelName]);
                    }
                }

                if (isset($taskDef['sprint'], $projectSprints[$projectIndex][$taskDef['sprint']])) {
                    $task->setSprint($projectSprints[$projectIndex][$taskDef['sprint']]);
                }

                $em->persist($task);
            }
        }

        // Align runtime sequences with the explicit references injected above so that
        // the next UI-driven create (next(SequencePrefixEnum::Project) etc.) doesn't
        // collide with PRJ-000001 / TSK-000001 / PRJC-000001.
        $em->flush();
        $connection = $em->getConnection();
    }

    // ── Notes / Markdown ──────────────────────────────────────────────────────

    /**
     * Seed a small wiki-style notebook on the admin demo user (index 0).
     * Showcases hierarchy (Welcome → Getting Started), tags, wiki-links
     * (`[[Welcome]]`, `[[Tasks]]`), and varied markdown features so the
     * Markdown sub-module has something to render out of the box.
     *
     * @param User[] $users
     */
    private function createMarkdownNotes(EntityManagerInterface $em, array $users): void
    {
        if ([] === $users) {
            return;
        }

        /** @var class-string<MarkdownNote> $noteClass */
        $noteClass = $em->getClassMetadata(MarkdownNoteInterface::class)->getName();

        $owner = $users[0];
        $agency = $owner->getAgency();
        $now = new DateTimeImmutable();

        $defs = [
            [
                'title' => 'Welcome',
                'tags' => ['demo', 'guide'],
                'content' => <<<MD
                    # Welcome

                    This is your demo notebook — a small showcase of the **Markdown** notes
                    sub-module ported from Onyx.

                    Browse the tree on the left, or jump to:
                    - [[Getting Started]] — markdown syntax reference
                    - [[Tasks]] — a sample checklist
                    - [[Random thoughts]] — quick capture

                    > Try renaming this note: every `[[Welcome]]` link in your other notes
                    > will update automatically.
                    MD,
                'parent' => null,
            ],
            [
                'title' => 'Getting Started',
                'tags' => ['demo', 'guide'],
                'content' => <<<MD
                    # Getting Started

                    A quick taste of the supported syntax.

                    ## Inline formatting

                    **bold**, *italic*, ~~strikethrough~~, `inline code`, [external link](https://example.com).

                    ## Lists

                    - apples
                    - oranges
                      - clementines
                    - bananas

                    ## Checklist

                    - [x] Read [[Welcome]]
                    - [ ] Open [[Tasks]]
                    - [ ] Doodle in [[Random thoughts]]

                    ## Code block

                    ```php
                    function greet(string \$name): string
                    {
                        return "Hello, {\$name}!";
                    }
                    ```

                    ## Quote

                    > Wiki-links use `[[Title]]` and point to other notes by title (case-insensitive).
                    MD,
                'parent' => 'Welcome',
            ],
            [
                'title' => 'Tasks',
                'tags' => ['demo', 'todo'],
                'content' => <<<MD
                    # Tasks

                    A sample checklist. Backlinks pane should show [[Welcome]] and
                    [[Getting Started]] linking here.

                    - [ ] Add a new note from the sidebar `+`
                    - [ ] Move this note under [[Welcome]] (drag & drop)
                    - [ ] Open the graph view to see the wiki-link web
                    - [x] Read the intro
                    MD,
                'parent' => null,
            ],
            [
                'title' => 'Random thoughts',
                'tags' => ['demo'],
                'content' => <<<MD
                    # Random thoughts

                    Whatever comes to mind. No structure required.

                    Today's todo: revisit [[Tasks]] tonight.
                    MD,
                'parent' => null,
            ],
        ];

        $byTitle = [];
        foreach ($defs as $i => $def) {
            $note = new $noteClass();
            $note->setUser($owner);
            $note->setAgency($agency);
            $note->setTitle($def['title']);
            $note->setContent($def['content']);
            $note->setTags($def['tags']);
            $note->setPosition($i);

            if (null !== $def['parent'] && isset($byTitle[$def['parent']])) {
                $note->setParent($byTitle[$def['parent']]);
            }

            // Lifecycle callbacks don't fire for direct persist + manual flush
            // in the same operation reliably across all Doctrine versions when
            // touching MappedSuperclass + trait properties — set explicitly.
            new ReflectionProperty(AbstractMarkdownNote::class, 'createdAt')->setValue($note, $now);
            new ReflectionProperty(AbstractMarkdownNote::class, 'updatedAt')->setValue($note, $now);

            $em->persist($note);
            $byTitle[$def['title']] = $note;
        }
    }

    // ── Menus ─────────────────────────────────────────────────────────────────

    /** @param Media[] $media */
    private function createMenuItems(EntityManagerInterface $em, array $media): void
    {
        // ── Ensure menus exist ────────────────────────────────────────────────
        $primary = $em->getRepository(Menu::class)->findOneBy(['location' => 'primary'])
            ?? new Menu()->setName('Menu principal')->setLocation('primary');
        $em->persist($primary);

        $footer = $em->getRepository(Menu::class)->findOneBy(['location' => 'footer'])
            ?? new Menu()->setName('Menu pied de page')->setLocation('footer');
        $em->persist($footer);

        $account = $em->getRepository(Menu::class)->findOneBy(['location' => 'account'])
            ?? new Menu()->setName('Menu compte')->setLocation('account');
        $em->persist($account);

        // ── Retrieve real entities to link ────────────────────────────────────
        $pageType = $em->getRepository(PostType::class)->findOneBy(['slug' => 'page']);
        $articleType = $em->getRepository(PostType::class)->findOneBy(['slug' => 'article']);
        $contactForm = $em->getRepository(Form::class)->findOneBy([]);

        // ── Create Page posts that don't exist yet ────────────────────────────
        $pageDefs = [
            ['fr_title' => 'Notre histoire', 'fr_slug' => 'notre-histoire', 'en_title' => 'Our Story',     'en_slug' => 'our-story',
                'fr_text' => 'Aurora Tech est née en 2022 de la conviction qu\'une PME mérite les mêmes outils qu\'un grand groupe. '.self::LOREM,
                'en_text' => 'Aurora Tech was founded in 2022 with the belief that SMEs deserve the same tools as large corporations. '.self::LOREM,
                'media' => $media[0] ?? null],
            ['fr_title' => 'Solutions Aurora', 'fr_slug' => 'solutions', 'en_title' => 'Aurora Solutions', 'en_slug' => 'solutions',
                'fr_text' => 'De la gestion commerciale à la facturation, Aurora couvre l\'ensemble de vos processus métier. '.self::LOREM,
                'en_text' => 'From sales management to invoicing, Aurora covers all your business processes. '.self::LOREM,
                'media' => $media[1] ?? null],
            ['fr_title' => 'Tarifs', 'fr_slug' => 'tarifs', 'en_title' => 'Pricing', 'en_slug' => 'pricing',
                'fr_text' => 'Choisissez la formule adaptée à votre équipe. Tous nos abonnements incluent les mises à jour et le support. '.self::LOREM,
                'en_text' => 'Choose the plan that fits your team. All subscriptions include updates and support. '.self::LOREM,
                'media' => $media[2] ?? null],
            ['fr_title' => 'Ressources', 'fr_slug' => 'ressources', 'en_title' => 'Resources', 'en_slug' => 'resources',
                'fr_text' => 'Documentation, tutoriels vidéo, webinaires et modèles prêts à l\'emploi pour démarrer rapidement. '.self::LOREM,
                'en_text' => 'Documentation, video tutorials, webinars and ready-to-use templates to get started quickly. '.self::LOREM,
                'media' => $media[3] ?? null],
            ['fr_title' => 'À propos', 'fr_slug' => 'a-propos', 'en_title' => 'About Us', 'en_slug' => 'about-us',
                'fr_text' => 'Une équipe de passionnés qui construit la suite logicielle dont les PME françaises ont besoin. '.self::LOREM,
                'en_text' => 'A passionate team building the software suite that French SMEs need. '.self::LOREM,
                'media' => $media[0] ?? null],
            ['fr_title' => 'Mentions légales', 'fr_slug' => 'cgu', 'en_title' => 'Terms of Service', 'en_slug' => 'terms',
                'fr_text' => 'Conditions générales d\'utilisation de la plateforme Aurora. '.self::LOREM, 'en_text' => self::LOREM, 'media' => null],
            ['fr_title' => 'Politique de confidentialité', 'fr_slug' => 'confidentialite', 'en_title' => 'Privacy Policy', 'en_slug' => 'privacy',
                'fr_text' => 'Comment nous collectons, utilisons et protégeons vos données personnelles. '.self::LOREM, 'en_text' => self::LOREM, 'media' => null],
            ['fr_title' => 'Équipe Aurora', 'fr_slug' => 'equipe', 'en_title' => 'Our Team', 'en_slug' => 'team',
                'fr_text' => 'Rencontrez les personnes qui construisent Aurora chaque jour. '.self::LOREM, 'en_text' => self::LOREM, 'media' => $media[2] ?? null],
        ];

        /** @var array<string, Post> $pages key = fr_slug */
        $pages = [];
        foreach ($pageDefs as $pd) {
            if (!$pageType instanceof PostType) {
                break;
            }

            $page = new Post();
            $page->setPostType($pageType)->setStatus(PostStatusEnum::Published)->setFeaturedMedia($pd['media']);
            $trFr = new PostTranslation();
            $trFr->setPost($page)->setLocale('fr')->setTitle($pd['fr_title'])->setSlug($pd['fr_slug'])
                 ->setBlocks([['type' => 'paragraph', 'data' => ['text' => $pd['fr_text']]]])
                 ->setSearchContent($pd['fr_text']);
            $trEn = new PostTranslation();
            $trEn->setPost($page)->setLocale('en')->setTitle($pd['en_title'])->setSlug($pd['en_slug'])
                 ->setBlocks([['type' => 'paragraph', 'data' => ['text' => $pd['en_text']]]])
                 ->setSearchContent($pd['en_text']);
            $em->persist($trFr);
            $em->persist($trEn);
            $em->persist($page);
            $pages[$pd['fr_slug']] = $page;
        }

        $em->flush();

        // ── Helper: create a menu item ────────────────────────────────────────
        $addItem = function (
            Menu $menu,
            string $frLabel,
            string $enLabel,
            MenuItemTargetTypeEnum $type,
            int $pos,
            ?int $targetId = null,
            ?string $customUrl = null,
            ?MenuItem $parent = null,
        ) use ($em): MenuItem {
            $item = new MenuItem();
            $item->setMenu($menu)->setTargetType($type)->setPosition($pos);
            if (null !== $targetId) {
                $item->setTargetId($targetId);
            }

            if (null !== $customUrl) {
                $item->setCustomUrl($customUrl);
            }

            if ($parent instanceof MenuItem) {
                $item->setParent($parent);
            }

            $em->persist($item);
            foreach (['fr', 'en'] as $locale) {
                $tr = new MenuItemTranslation();
                $tr->setMenuItem($item)->setLocale($locale)->setLabel('fr' === $locale ? $frLabel : $enLabel);
                $em->persist($tr);
            }

            return $item;
        };

        // ── Primary navigation ────────────────────────────────────────────────
        $pos = 0;
        $addItem($primary, 'Accueil', 'Home', MenuItemTargetTypeEnum::Home, $pos++);
        if (isset($pages['notre-histoire'])) {
            $addItem($primary, 'Notre histoire', 'Our Story', MenuItemTargetTypeEnum::Post, $pos++, $pages['notre-histoire']->getId());
        }

        if (isset($pages['solutions'])) {
            $addItem($primary, 'Solutions', 'Solutions', MenuItemTargetTypeEnum::Post, $pos++, $pages['solutions']->getId());
        }

        if ($articleType instanceof PostType) {
            $addItem($primary, 'Blog', 'Blog', MenuItemTargetTypeEnum::PostTypeArchive, $pos++, $articleType->getId());
        }

        if (isset($pages['tarifs'])) {
            $addItem($primary, 'Tarifs', 'Pricing', MenuItemTargetTypeEnum::Post, $pos++, $pages['tarifs']->getId());
        }

        // Boutique → front shop (custom URL, locale-prefixed in front routing)
        $addItem($primary, 'Boutique', 'Shop', MenuItemTargetTypeEnum::CustomUrl, $pos++, null, '/fr/shop');
        if (isset($pages['ressources'])) {
            $addItem($primary, 'Ressources', 'Resources', MenuItemTargetTypeEnum::Post, $pos++, $pages['ressources']->getId());
        }

        if (isset($pages['a-propos'])) {
            $addItem($primary, 'À propos', 'About', MenuItemTargetTypeEnum::Post, $pos++, $pages['a-propos']->getId());
        }

        // Contact → link to the contact form's front page (use the form's slug via custom URL)
        if ($contactForm instanceof Form) {
            $contactFormSlug = $contactForm->getTranslation('fr')?->getSlug();
            if (null !== $contactFormSlug) {
                $addItem($primary, 'Contact', 'Contact', MenuItemTargetTypeEnum::CustomUrl, $pos++, null, '/fr/forms/'.$contactFormSlug);
            }
        }

        // ── Footer navigation (grouped sections) ─────────────────────────────
        $pos = 0;

        // Produit
        $sect = $addItem($footer, 'Produit', 'Product', MenuItemTargetTypeEnum::CustomUrl, $pos++);
        $addItem($footer, 'Fonctionnalités', 'Features', MenuItemTargetTypeEnum::Post, 0, isset($pages['solutions']) ? $pages['solutions']->getId() : null, null, $sect);
        $addItem($footer, 'Tarifs', 'Pricing', MenuItemTargetTypeEnum::Post, 1, isset($pages['tarifs']) ? $pages['tarifs']->getId() : null, null, $sect);
        $addItem($footer, 'Roadmap', 'Roadmap', MenuItemTargetTypeEnum::CustomUrl, 2, null, '/roadmap', $sect);
        $addItem($footer, 'Blog', 'Blog', $articleType instanceof PostType ? MenuItemTargetTypeEnum::PostTypeArchive : MenuItemTargetTypeEnum::CustomUrl, 3, $articleType?->getId(), null, $sect);

        // Ressources
        $sect2 = $addItem($footer, 'Ressources', 'Resources', MenuItemTargetTypeEnum::Post, $pos++, isset($pages['ressources']) ? $pages['ressources']->getId() : null);
        $addItem($footer, 'Documentation', 'Documentation', MenuItemTargetTypeEnum::CustomUrl, 0, null, '/docs', $sect2);
        $addItem($footer, 'Tutoriels', 'Tutorials', MenuItemTargetTypeEnum::CustomUrl, 1, null, '/tutoriels', $sect2);
        if ($contactForm instanceof Form) {
            $contactFormSlug = $contactForm->getTranslation('fr')?->getSlug();
            $addItem($footer, 'Formulaire contact', 'Contact form', MenuItemTargetTypeEnum::CustomUrl, 2, null, null !== $contactFormSlug ? '/fr/forms/'.$contactFormSlug : '/contact', $sect2);
        }

        // Entreprise
        $sect3 = $addItem($footer, 'Entreprise', 'Company', MenuItemTargetTypeEnum::CustomUrl, $pos++);
        $addItem($footer, 'À propos', 'About', MenuItemTargetTypeEnum::Post, 0, isset($pages['a-propos']) ? $pages['a-propos']->getId() : null, null, $sect3);
        $addItem($footer, 'Équipe', 'Team', MenuItemTargetTypeEnum::Post, 1, isset($pages['equipe']) ? $pages['equipe']->getId() : null, null, $sect3);
        $addItem($footer, 'Carrières', 'Careers', MenuItemTargetTypeEnum::CustomUrl, 2, null, '/carrieres', $sect3);

        // Légal
        $sect4 = $addItem($footer, 'Légal', 'Legal', MenuItemTargetTypeEnum::CustomUrl, $pos++);
        $addItem($footer, 'CGU', 'Terms', MenuItemTargetTypeEnum::Post, 0, isset($pages['cgu']) ? $pages['cgu']->getId() : null, null, $sect4);
        $addItem($footer, 'Confidentialité', 'Privacy', MenuItemTargetTypeEnum::Post, 1, isset($pages['confidentialite']) ? $pages['confidentialite']->getId() : null, null, $sect4);
        $addItem($footer, 'Cookies', 'Cookies', MenuItemTargetTypeEnum::CustomUrl, 2, null, '/cookies', $sect4);
    }
}
