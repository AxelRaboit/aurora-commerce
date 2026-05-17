# Documentation Aurora Core

## Philosophie

| Fichier | Contenu |
|---|---|
| [philosophy.md](philosophy.md) | Principes fondateurs, pattern d'extensibilité, système de modules |

## todo/ — Tâches techniques en attente

| Fichier | Contenu |
|---|---|
| [todo/](todo/README.md) | Index des TODOs techniques, organisés par module puis par topic (catalogue, tarification, livraison…) |
| [module_roadmap.md](todo/module_roadmap.md) | Modules à venir, classés par priorité |
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

## ops/ — Prérequis bundle

| Fichier | Contenu |
|---|---|
| [prerequisites.md](ops/prerequisites.md) | Checklist exhaustive des dépendances système, PHP, Node, modèles Ollama, vars d'env |

> Tout ce qui touche au **déploiement** d'un projet client (séquence install-prod, systemd, mod_xsendfile, setup OCR) vit côté aurora-client : [`docs/aurora-client/deployment/`](../aurora-client/README.md#-déploiement-production).

