<?php

declare(strict_types=1);

namespace Aurora\Module\Project\DataFixtures;

use Aurora\Core\DataFixtures\CoreDemoFixtures;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\DataFixtures\CrmDemoFixtures;
use Aurora\Module\Platform\User\Entity\User;
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

/**
 * Demo project boards: projects, columns, labels, sprints and tasks linked to
 * the demo users and (if installed) CRM companies/contacts. Dev/test only.
 */
class ProjectDemoFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function getDependencies(): array
    {
        $deps = [CoreDemoFixtures::class];
        if (class_exists(CrmDemoFixtures::class)) {
            $deps[] = CrmDemoFixtures::class;
        }

        return $deps;
    }

    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);

        $users = [];
        for ($i = 0; $i < CoreDemoFixtures::USER_COUNT; ++$i) {
            $users[] = $this->getReference(CoreDemoFixtures::userRef($i), User::class);
        }

        $companies = [];
        if (class_exists(CrmDemoFixtures::class)) {
            for ($i = 0; $this->hasReference(CrmDemoFixtures::companyRef($i), Company::class); ++$i) {
                $companies[] = $this->getReference(CrmDemoFixtures::companyRef($i), Company::class);
            }
        }

        $contacts = [];
        if (class_exists(CrmDemoFixtures::class)) {
            for ($i = 0; $this->hasReference(CrmDemoFixtures::contactRef($i), Contact::class); ++$i) {
                $contacts[] = $this->getReference(CrmDemoFixtures::contactRef($i), Contact::class);
            }
        }

        $this->createProjects($manager, $users, $companies, $contacts);

        $manager->flush();
    }

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
                ->setCrmCompanyId((int) $companies[$def['company']]->getId());
            $projectContactIds = [];
            foreach ($def['contacts'] as $contactIndex) {
                if (isset($contacts[$contactIndex])) {
                    $projectContactIds[] = (int) $contacts[$contactIndex]->getId();
                }
            }

            $project->setCrmContactIds($projectContactIds);

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
}
