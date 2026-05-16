# Aurora-client — Developer Guide

Ce dossier contient la documentation destinée aux développeurs qui travaillent
dans un projet client Aurora. Les docs aurora-core (architecture du bundle,
conventions d'extensibilité, ops) sont dans [`aurora-core/`](../aurora-core/README.md).

## 🚀 Démarrage

| Fichier | Contenu |
|---|---|
| [getting-started/philosophy.md](getting-started/philosophy.md) | Philosophie du projet — les deux modes de travail, ce qu'on ne fait pas |
| [getting-started/setup.md](getting-started/setup.md) | Installation locale — première mise en route |
| [getting-started/architecture.md](getting-started/architecture.md) | Structure du projet, relation avec aurora-core |

## 🛠️ Développement quotidien

| Fichier | Contenu |
|---|---|
| [dev/dev_workflow.md](dev/dev_workflow.md) | Commandes du quotidien |
| [dev/database.md](dev/database.md) | Migrations, fixtures, séquences |
| [dev/assets_vue.md](dev/assets_vue.md) | Composants Vue côté client |
| [dev/update_aurora.md](dev/update_aurora.md) | Mettre à jour aurora-core (`make aurora-update`) |

## 🔧 Étendre Aurora

| Fichier | Contenu |
|---|---|
| [extending/extend_entity.md](extending/extend_entity.md) | Étendre une entité Aurora (champ, DTO, Manager, Serializer, Vue) |
| [extending/add_module.md](extending/add_module.md) | Créer un module client complet |
| [extending/custom_permissions.md](extending/custom_permissions.md) | Ajouter des permissions custom (granularité view/create/edit/delete) |
