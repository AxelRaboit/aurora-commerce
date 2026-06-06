<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\DataFixtures;

use Aurora\Core\Money\Enum\CurrencyEnum;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Ged\DataFixtures\GedDemoFixtures;
use Aurora\Module\Ged\Document\Entity\Document;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Demo billing: invoices and tiers (with sample attachments from demo media).
 * Dev/test only.
 */
class BillingDemoFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
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

        $this->createBilling($manager, $media);

        $manager->flush();
    }

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

        // Invoice.document references a GED Document now (W3 pattern).
        // The demo invoices ship without an attached doc; users can run
        // the OCR flow from /backend/billing/ocr to seed a real one.
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
}
