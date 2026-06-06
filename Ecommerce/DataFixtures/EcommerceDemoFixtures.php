<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\DataFixtures;

use Aurora\Core\DataFixtures\CoreDemoFixtures;
use Aurora\Core\Money\Enum\CurrencyEnum;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategory;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryTranslation;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTag;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagTranslation;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Entity\OrderLine;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Aurora\Module\Erp\DataFixtures\ErpDemoFixtures;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Ged\DataFixtures\GedDemoFixtures;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Platform\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Demo ecommerce listings (+ categories and tags) built on the ERP products
 * and demo media. Dev/test only.
 */
class EcommerceDemoFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function getDependencies(): array
    {
        return [CoreDemoFixtures::class, GedDemoFixtures::class, ErpDemoFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);

        $users = [];
        for ($i = 0; $i < CoreDemoFixtures::USER_COUNT; ++$i) {
            $users[] = $this->getReference(CoreDemoFixtures::userRef($i), User::class);
        }

        $media = [];
        for ($i = 0; $this->hasReference(GedDemoFixtures::mediaRef($i), Document::class); ++$i) {
            $media[] = $this->getReference(GedDemoFixtures::mediaRef($i), Document::class);
        }

        $products = [];
        for ($i = 0; $this->hasReference(ErpDemoFixtures::productRef($i), Product::class); ++$i) {
            $products[] = $this->getReference(ErpDemoFixtures::productRef($i), Product::class);
        }

        $listings = $this->createEcommerce($manager, $products, $media, $users);
        $this->createListingCategories($manager, $listings);
        $this->createListingTags($manager, $listings);

        $manager->flush();
    }

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
}
