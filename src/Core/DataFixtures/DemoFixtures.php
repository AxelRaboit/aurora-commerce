<?php

declare(strict_types=1);

namespace Aurora\Core\DataFixtures;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Media\Entity\Media;
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
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategory;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryItem;
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

        $this->createEditorial($manager, $postType, $media, $users);
        [$companies, $contacts] = $this->createCrm($manager, $users);
        $products = $this->createErp($manager, $media);
        $listings = $this->createEcommerce($manager, $products, $media, $users);
        $this->createBilling($manager, $media);
        $this->createPhoto($manager, $media, $users, $contacts);
        $this->createGed($manager, $media);

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

    private function createEditorial(EntityManagerInterface $em, ?PostType $postType, array $media, array $users): void
    {
        if (!$postType instanceof PostType) {
            return;
        }

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
                 ->setPublishedAt(new DateTimeImmutable('-'.($idx + 1).' weeks'));

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
        }
    }

    // ── CRM ───────────────────────────────────────────────────────────────────

    /** @return array{Company[], Contact[]} */
    private function createCrm(EntityManagerInterface $em, array $users): array
    {
        $companies = [];
        $companyDefs = [
            ['name' => 'Tech Innovation SARL',   'industry' => 'Informatique & Logiciels', 'website' => 'https://tech-innovation.fr', 'phone' => '+33 1 42 00 11 22', 'address' => '15 rue de la Paix, 75001 Paris'],
            ['name' => 'BioMed France',           'industry' => 'Santé & Biotechnologies',  'website' => 'https://biomed-france.com',   'phone' => '+33 4 91 55 66 77', 'address' => '8 avenue du Prado, 13008 Marseille'],
            ['name' => 'Retail Connect SAS',      'industry' => 'Commerce & Distribution',  'website' => 'https://retail-connect.fr',   'phone' => '+33 4 72 11 33 55', 'address' => '42 cours Gambetta, 69007 Lyon'],
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
            ['first' => 'Pierre',  'last' => 'Dubois',   'email' => 'pierre.dubois@tech-innovation.fr', 'phone' => '+33 6 12 34 56 78', 'company' => 0],
            ['first' => 'Camille', 'last' => 'Leroy',    'email' => 'c.leroy@biomed-france.com',        'phone' => '+33 6 23 45 67 89', 'company' => 1],
            ['first' => 'François', 'last' => 'Moreau',   'email' => 'f.moreau@retail-connect.fr',       'phone' => '+33 6 34 56 78 90', 'company' => 2],
            ['first' => 'Julie',   'last' => 'Chen',     'email' => 'julie.chen@tech-innovation.fr',    'phone' => '+33 6 45 67 89 01', 'company' => 0],
            ['first' => 'Marc',    'last' => 'Fontaine', 'email' => 'marc.fontaine@prospect.com',       'phone' => '+33 6 56 78 90 12', 'company' => null],
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
            ['name' => 'Projet CRM Sur-mesure',     'stage' => DealStageEnum::Won,         'value' => '45000.00', 'contact' => 0, 'company' => 0, 'closing' => '-1 month'],
            ['name' => 'Refonte Site Web BioMed',   'stage' => DealStageEnum::Proposal,    'value' => '12000.00', 'contact' => 1, 'company' => 1, 'closing' => '+2 months'],
            ['name' => 'Solution E-commerce',        'stage' => DealStageEnum::Negotiation, 'value' => '28500.00', 'contact' => 2, 'company' => 2, 'closing' => '+3 weeks'],
            ['name' => 'Formation équipe dev',       'stage' => DealStageEnum::Qualified,   'value' => '4800.00',  'contact' => 3, 'company' => 0, 'closing' => '+6 weeks'],
            ['name' => 'Audit infrastructure SI',    'stage' => DealStageEnum::Lead,        'value' => '8000.00',  'contact' => 4, 'company' => null, 'closing' => '+2 months'],
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
            ['ref' => 'LIC-CRM-001',  'name' => 'Aurora CRM — Licence annuelle',        'type' => ProductTypeEnum::Digital,   'status' => ProductStatusEnum::Active,   'price' => 79900,  'stock' => null, 'desc' => 'Accès complet au module CRM Aurora. Contacts, entreprises, deals, pipeline Kanban. Licence par utilisateur / an.'],
            ['ref' => 'LIC-ERP-001',  'name' => 'Aurora ERP — Licence annuelle',         'type' => ProductTypeEnum::Digital,   'status' => ProductStatusEnum::Active,   'price' => 99900,  'stock' => null, 'desc' => 'Gestion des produits, stocks, fournisseurs et commandes. Licence par utilisateur / an.'],
            ['ref' => 'HW-NAS-001',   'name' => 'Serveur NAS 4 baies (8 To)',            'type' => ProductTypeEnum::Physical,  'status' => ProductStatusEnum::Active,   'price' => 64900,  'stock' => 12,   'desc' => 'Serveur de stockage réseau 4 baies, 8 To (2×4 To RAID 1). Idéal pour la GED et sauvegardes.'],
            ['ref' => 'HW-USB-010',   'name' => 'Clé USB sécurisée 256 Go × 10',         'type' => ProductTypeEnum::Physical,  'status' => ProductStatusEnum::Active,   'price' => 14900,  'stock' => 48,   'desc' => 'Pack de 10 clés USB chiffrées AES-256, 256 Go, compatibles USB-C et USB-A.'],
            ['ref' => 'SRV-DEV-001',  'name' => 'Formation Développement Web (3 jours)', 'type' => ProductTypeEnum::Service,   'status' => ProductStatusEnum::Active,   'price' => 189000, 'stock' => null, 'desc' => 'Formation intensive 3 jours : Symfony 7, Vue.js 3, Vite. Groupe de 5 à 8 personnes. Intra-entreprise.'],
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
            ['product' => 0, 'slug' => 'aurora-crm-licence-annuelle',       'title' => 'Aurora CRM — Licence annuelle',        'desc' => 'Gérez tous vos clients, contacts et deals depuis une interface unifiée. Essai 30 jours inclus.', 'media' => 0],
            ['product' => 2, 'slug' => 'serveur-nas-4-baies',               'title' => 'Serveur NAS 4 baies 8 To',              'desc' => 'La solution de stockage réseau idéale pour les PME. Livraison en 48h, installation incluse.', 'media' => 1],
            ['product' => 4, 'slug' => 'formation-developpement-web-3-jours', 'title' => 'Formation Développement Web 3 jours',   'desc' => 'Devenez expert Symfony + Vue.js en 3 jours intensifs. Certification incluse.', 'media' => 3],
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
            ['type' => TiersTypeEnum::Supplier, 'name' => 'Dell Technologies France', 'email' => 'business@dell.com',        'phone' => '+33 1 70 37 60 00', 'address' => '1 Rond-Point Benjamin Franklin, 34000 Montpellier'],
            ['type' => TiersTypeEnum::Supplier, 'name' => 'SFR Business',             'email' => 'sfr-business@sfr.fr',      'phone' => '+33 9 70 00 19 19', 'address' => '16 rue du Général Foy, 75008 Paris'],
            ['type' => TiersTypeEnum::Client,   'name' => 'Tech Innovation SARL',     'email' => 'compta@tech-innovation.fr', 'phone' => '+33 1 42 00 11 22', 'address' => '15 rue de la Paix, 75001 Paris'],
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
    }

    // ── Photo ─────────────────────────────────────────────────────────────────

    private function createPhoto(EntityManagerInterface $em, array $media, array $users, array $contacts): void
    {
        [$marie] = $users;

        $gallery = new Gallery();
        $gallery->setTitle('Portfolio Projets Aurora Tech 2025')
                ->setSlug('portfolio-aurora-tech-2025')
                ->setDescription('Sélection de visuels réalisés pour nos projets clients en 2025. Galerie privée — accès sur invitation.')
                ->setCreatedBy($marie)
                ->setAllowOriginals(true)
                ->setAllowZipDownload(true)
                ->setAllowVisitorComments(false);

        if (isset($media[0])) {
            $gallery->setCoverMedia($media[0]);
        }

        $em->persist($gallery);

        foreach (array_slice($media, 0, 4) as $i => $m) {
            if (!str_contains((string) $m->getMimeType(), 'image')) {
                continue;
            }

            $item = new GalleryItem();
            $item->setGallery($gallery)
                 ->setMedia($m)
                 ->setNumber($i + 1);
            $em->persist($item);
        }
    }

    // ── GED ───────────────────────────────────────────────────────────────────

    private function createGed(EntityManagerInterface $em, array $media): void
    {
        $catDefs = [
            ['name' => 'Contrats Clients',       'slug' => 'contrats-clients',       'desc' => 'Contrats signés avec nos clients et partenaires commerciaux.'],
            ['name' => 'Documentation Technique', 'slug' => 'doc-technique',          'desc' => 'Guides d\'installation, spécifications et manuels techniques.'],
            ['name' => 'Ressources Marketing',    'slug' => 'ressources-marketing',   'desc' => 'Visuels, présentations et supports de communication.'],
        ];
        $categories = [];
        foreach ($catDefs as $def) {
            $c = new DocumentCategory();
            $c->setName($def['name'])->setSlug($def['slug'])->setDescription($def['desc']);
            $em->persist($c);
            $categories[] = $c;
        }

        $docDefs = [
            ['title' => 'Contrat Tech Innovation SARL 2025',          'cat' => 0, 'status' => DocumentStatusEnum::Published, 'desc' => 'Contrat de prestation de services signé le 15 janvier 2025. Durée : 12 mois renouvelable.', 'file' => 5],
            ['title' => "Guide d'installation Aurora v2.0",           'cat' => 1, 'status' => DocumentStatusEnum::Published, 'desc' => 'Documentation complète pour installer et configurer Aurora en production.', 'file' => null],
            ['title' => 'Rapport Annuel 2024 — Aurora Tech',           'cat' => 0, 'status' => DocumentStatusEnum::Draft,     'desc' => 'Bilan financier et opérationnel de l\'exercice 2024. En cours de validation.', 'file' => null],
            ['title' => 'Charte Graphique Aurora — Brand Guidelines',  'cat' => 2, 'status' => DocumentStatusEnum::Published, 'desc' => 'Couleurs, typographies, logos et règles d\'utilisation de la marque Aurora.', 'file' => 0],
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
}
