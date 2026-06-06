<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\DataFixtures;

use Aurora\Core\Money\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use Aurora\Module\Ged\DataFixtures\GedDemoFixtures;
use Aurora\Module\Ged\Document\Entity\Document;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Demo ERP products (linked to demo media). Exposed via {@see productRef} for
 * the ecommerce listings demo. Dev/test only.
 */
class ErpDemoFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public static function productRef(int $index): string
    {
        return 'erp_demo_product_'.$index;
    }

    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function getDependencies(): array
    {
        return [GedDemoFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);

        $media = [];
        for ($i = 0; $this->hasReference(GedDemoFixtures::mediaRef($i), Document::class); ++$i) {
            $media[] = $this->getReference(GedDemoFixtures::mediaRef($i), Document::class);
        }

        $products = $this->createErp($manager, $media);

        foreach ($products as $i => $product) {
            $this->addReference(self::productRef($i), $product);
        }

        $manager->flush();
    }

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
}
