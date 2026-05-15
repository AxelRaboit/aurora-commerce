# Documentation Aurora Core

## Philosophie

| Fichier | Contenu |
|---|---|
| [philosophy.md](philosophy.md) | Principes fondateurs, pattern d'extensibilité, système de modules |

## todo/ — Tâches techniques en attente

| Fichier | Contenu |
|---|---|
| [todo/](dev/todo/README.md) | Index des TODOs techniques, organisés par module puis par topic (catalogue, tarification, livraison…) |
| [scheduler.md](dev/scheduler.md) | Symfony Scheduler — ajouter des tâches récurrentes, convention fichiers temporaires, tâches existantes |

## dev/ — Guides développeur

| Fichier | Contenu |
|---|---|
| [app_architecture.md](dev/app_architecture.md) | Vue d'ensemble de l'architecture (Symfony, Vue 3, modules) |
| [entity_extensibility_convention.md](dev/entity_extensibility_convention.md) | Convention d'extensibilité des entités (5 couches, pattern Sylius) |
| [extending_aurora.md](dev/extending_aurora.md) | Comment utiliser Aurora Core comme base d'une app client |
| [extending_agency_pilot.md](dev/extending_agency_pilot.md) | Guide pas-à-pas d'extension complète (exemple Agency) |
| [client_quickstart.md](dev/client_quickstart.md) | Démarrage rapide côté client consommateur |
| [form_validation.md](dev/form_validation.md) | Convention de validation des formulaires (DTO, PayloadValidator, useForm) |

## ops/ — Infrastructure & déploiement

| Fichier | Contenu |
|---|---|
| [worker_systemd.md](ops/worker_systemd.md) | Configuration du service systemd `aurora-worker` en production |
| [ocr_setup.md](ops/ocr_setup.md) | Mise en place du pipeline OCR (Billing) |

## product/ — Roadmap & planification

| Fichier | Contenu |
|---|---|
| [module_roadmap.md](product/module_roadmap.md) | Modules à venir, classés par priorité |
