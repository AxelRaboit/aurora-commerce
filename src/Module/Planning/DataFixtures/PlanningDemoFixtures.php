<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\DataFixtures;

use Aurora\Core\DataFixtures\CoreDemoFixtures;
use Aurora\Module\Planning\Event\Entity\PlanningEvent;
use Aurora\Module\Planning\Event\Enum\PlanningEventStatusEnum;
use Aurora\Module\Planning\Planning\Entity\Planning;
use Aurora\Module\Planning\Planning\Enum\PlanningVisibilityEnum;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

use function assert;
use function date;

/**
 * Demo plannings (dev team, direction, leave, sales) with realistic events
 * owned by the demo users seeded by {@see CoreDemoFixtures}. Dev/test only.
 */
class PlanningDemoFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function getDependencies(): array
    {
        return [CoreDemoFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);

        $users = [];
        for ($i = 0; $i < CoreDemoFixtures::USER_COUNT; ++$i) {
            $users[] = $this->getReference(CoreDemoFixtures::userRef($i), User::class);
        }

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
        $manager->persist($p1);

        // Planning 2 — Direction (privé)
        $p2 = new Planning();
        $p2->setName('Comités de direction')
           ->setDescription('Réunions CODIR, board et revues stratégiques.')
           ->setColor('#8b5cf6')
           ->setTimezone('Europe/Paris')
           ->setVisibility(PlanningVisibilityEnum::Private_)
           ->setOwner($admin);
        $manager->persist($p2);

        // Planning 3 — Congés & absences (public)
        $p3 = new Planning();
        $p3->setName('Congés & absences')
           ->setDescription('Suivi des congés payés, RTT et absences exceptionnelles.')
           ->setColor('#10b981')
           ->setTimezone('Europe/Paris')
           ->setVisibility(PlanningVisibilityEnum::Public_)
           ->setOwner($admin);
        $manager->persist($p3);

        // Planning 4 — Commercial
        $p4 = new Planning();
        $p4->setName('Agenda Commercial')
           ->setDescription('Rendez-vous clients, démos produit et appels de suivi.')
           ->setColor('#f59e0b')
           ->setTimezone('Europe/Paris')
           ->setVisibility(PlanningVisibilityEnum::Agency)
           ->setOwner($users[1] ?? $admin);
        $manager->persist($p4);

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
        $addEvent($manager, $p1, 'Daily stand-up', 'today 09:00', 'today 09:15', PlanningEventStatusEnum::Confirmed, 'Visio', 'Réunion quotidienne d\'avancement.');
        $addEvent($manager, $p1, 'Sprint review S24', 'this monday 14:00', 'this monday 16:00', PlanningEventStatusEnum::Confirmed, 'Salle Arctique', 'Démonstration des fonctionnalités livrées ce sprint.');
        $addEvent($manager, $p1, 'Sprint planning S25', 'next monday 10:00', 'next monday 12:00', PlanningEventStatusEnum::Confirmed, 'Salle Arctique', 'Planification et estimation du prochain sprint.');
        $addEvent($manager, $p1, 'Atelier architecture API', $now->modify('+3 days')->format('Y-m-d').' 09:30', $now->modify('+3 days')->format('Y-m-d').' 12:00', PlanningEventStatusEnum::Confirmed, 'Salle Boréale', 'Revue de la conception des nouveaux endpoints REST.');
        $addEvent($manager, $p1, 'Mise en production v2.4', $now->modify('+7 days')->format('Y-m-d').' 22:00', $now->modify('+8 days')->format('Y-m-d').' 01:00', PlanningEventStatusEnum::Tentative, null, 'Déploiement Aurora v2.4 en production. Fenêtre de maintenance.');
        $addEvent($manager, $p1, 'Formation Docker interne', $now->modify('-5 days')->format('Y-m-d').' 14:00', $now->modify('-5 days')->format('Y-m-d').' 17:00', PlanningEventStatusEnum::Confirmed, 'Salle Boréale', 'Formation containerisation pour l\'équipe dev.');
        $addEvent($manager, $p1, 'Rétrospective S23', $now->modify('-14 days')->format('Y-m-d').' 15:00', $now->modify('-14 days')->format('Y-m-d').' 16:30', PlanningEventStatusEnum::Confirmed, 'Visio', 'Bilan du sprint 23 — points positifs et axes d\'amélioration.');

        // Événements — Direction
        $addEvent($manager, $p2, 'CODIR mensuel — '.date('F Y'), $now->modify('first day of this month')->format('Y-m-d').' 09:00', $now->modify('first day of this month')->format('Y-m-d').' 12:00', PlanningEventStatusEnum::Confirmed, 'Salle de direction', 'Revue des KPIs et décisions stratégiques du mois.');
        $addEvent($manager, $p2, 'Revue budgétaire T2 2025', $now->modify('+10 days')->format('Y-m-d').' 10:00', $now->modify('+10 days')->format('Y-m-d').' 12:00', PlanningEventStatusEnum::Confirmed, 'Salle de direction', 'Analyse des dépenses et ajustements budgétaires T2.');
        $addEvent($manager, $p2, 'Board Aurora — juin 2025', $now->modify('+21 days')->format('Y-m-d').' 09:00', $now->modify('+21 days')->format('Y-m-d').' 17:00', PlanningEventStatusEnum::Tentative, 'Paris — Siège', 'Réunion annuelle des actionnaires et présentation des résultats.');

        // Événements — Congés
        $addEvent($manager, $p3, 'Congés Thomas Dubois', $now->modify('+14 days')->format('Y-m-d'), $now->modify('+21 days')->format('Y-m-d'), PlanningEventStatusEnum::Confirmed, null, null, true);
        $addEvent($manager, $p3, 'Congés Clara Lefèvre', $now->modify('+28 days')->format('Y-m-d'), $now->modify('+35 days')->format('Y-m-d'), PlanningEventStatusEnum::Confirmed, null, null, true);
        $addEvent($manager, $p3, 'RTT — Lucie Girard', $now->modify('+5 days')->format('Y-m-d'), $now->modify('+5 days')->format('Y-m-d'), PlanningEventStatusEnum::Confirmed, null, null, true);
        $addEvent($manager, $p3, 'Arrêt maladie Marc Rousseau', $now->modify('-3 days')->format('Y-m-d'), $now->modify('+2 days')->format('Y-m-d'), PlanningEventStatusEnum::Confirmed, null, null, true);

        // Événements — Commercial
        $addEvent($manager, $p4, 'Démo Aurora — BioMed France', $now->modify('+2 days')->format('Y-m-d').' 10:00', $now->modify('+2 days')->format('Y-m-d').' 11:30', PlanningEventStatusEnum::Confirmed, 'Visio Teams', 'Présentation des modules GED et Facturation à BioMed France.');
        $addEvent($manager, $p4, 'Déjeuner client — Retail Connect', $now->modify('+4 days')->format('Y-m-d').' 12:30', $now->modify('+4 days')->format('Y-m-d').' 14:00', PlanningEventStatusEnum::Confirmed, 'Restaurant Le Procope, Paris', 'Suivi commercial et renouvellement contrat 2025.');
        $addEvent($manager, $p4, 'Appel découverte — Novo Pharma', $now->modify('+6 days')->format('Y-m-d').' 15:00', $now->modify('+6 days')->format('Y-m-d').' 15:45', PlanningEventStatusEnum::Tentative, 'Téléphone', 'Premier contact — présentation Aurora et qualification du besoin.');
        $addEvent($manager, $p4, 'Salon Tech Paris 2025', $now->modify('+30 days')->format('Y-m-d'), $now->modify('+32 days')->format('Y-m-d'), PlanningEventStatusEnum::Confirmed, 'Paris Expo Porte de Versailles', 'Présence Aurora au salon — stand B42.', true);
        $addEvent($manager, $p4, 'Closing Tech Innovation SARL', $now->modify('-7 days')->format('Y-m-d').' 14:00', $now->modify('-7 days')->format('Y-m-d').' 15:00', PlanningEventStatusEnum::Confirmed, 'Visio', 'Signature finale du contrat de prestation 2025.');

        $manager->flush();
    }
}
