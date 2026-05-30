# Audit J1.1 — Inventaire & classification des modules

> Livrable de la **Phase 1.1** de `audit_monorepo_split.md`, jalon **J1**
> (cartographie commune). Snapshot au **2026-05-30**, branche
> `feat/monorepo-audit`. Métriques via `find`/`wc` sur `src/Module/*`.

## ⚠️ Écart avec l'audit initial

Les docs d'audit (rédigés avant J1) anticipaient **7 modules métier**
(Billing, Crm, Ecommerce, Editorial, Erp, Photo, Project) et un core
composé de « User + Configuration + Administration + Auth + Dashboard +
Ged ». La réalité est **18 modules**, et User/Auth/Agency/Service vivent
désormais **regroupés sous `Platform`** (pas de modules séparés). Il faut
mettre à jour la cible : le core = `Core/` + **Platform** + Configuration
+ Dev + Ged. Les modules métier sont **11**, pas 7.

## Tableau modules × métriques × statut

| Module | PHP LoC | Entités¹ | Ctrl | Vue | Tests | Statut | Dépend (hors core)² |
|---|---:|---:|---:|---:|---:|---|---|
| **Platform** | 7 399 | 15 | 14 | 13 | 25 | **CORE** | — |
| **Configuration** | 3 720 | 6 | 4 | 4 | 5 | **CORE** | — |
| **Dev** | 1 979 | 6 | 2 | 11 | 8 | **CORE** | — |
| **Ged** | 4 503 | 15 | 6 | 9 | 22 | **CORE** | — |
| **General** | 1 154 | 0 | 4 | 6 | 1 | **SPÉCIAL** (shell app) | Billing, Crm, Ecommerce, Editorial, Erp, Photo, Project |
| Assistant | 4 180 | 9 | 3 | 3 | 6 | métier (quasi-isolé) | — (General seul³) |
| Hr | 967 | 3 | 1 | 1 | 6 | métier **leaf** | — |
| Notes | 5 341 | 9 | 7 | 11 | 18 | métier **leaf** | — |
| PersonalFinance | 16 474 | 39 | 18 | 15 | 19 | métier **leaf** | — |
| Planning | 1 871 | 6 | 2 | 3 | 9 | métier **leaf** | — |
| Tools | 2 007 | 9 | 4 | 12 | 14 | métier **leaf** | — |
| Photo | 5 758 | 18 | 6 | 4 | 10 | métier | Crm |
| Billing | 6 333 | 12 | 5 | 8 | 3 | métier | Crm, Erp |
| Erp | 1 378 | 3 | 2 | 2 | 6 | métier | Ecommerce ⚠️ |
| Ecommerce | 7 524 | 27 | 11 | 16 | 17 | métier | Erp ⚠️ |
| Editorial | 15 954 | 60 | 18 | 36 | 38 | métier | Ecommerce |
| Crm | 3 736 | 12 | 8 | 7 | 11 | métier | Ecommerce, Editorial |
| Project | 5 690 | 27 | 5 | 1 | 33 | métier | Billing, Crm |

¹ Fichiers sous `*/Entity/` (Interface + Abstract + concrete comptent
séparément ; le nb d'entités logiques est ~⅓).
² « hors core » = imports `use Aurora\Module\X` où X n'est PAS
Platform/Configuration/Dev/Ged. Voir `dependency_graph.md` pour les poids.
³ Assistant n'importe que des modules core + `General` (1 ref, intégration
recherche/mountpoint) — à vérifier en J2, sinon **leaf**.

## Critères de classification appliqués

**CORE** (livré avec `aurora-core`, non-extractible) :
- **Platform** — User, Auth, Agency, Service. Socle d'authentification et
  de multi-tenant. Importé par **14 modules**. Sans lui rien ne tourne.
- **Configuration** — Setting, Theme, **`ModuleParameterEnum`** (la source
  de vérité des toggles de TOUS les modules). Importé par ~tous.
- **Dev** — Audit log (`AuditLog`), MountPoint. Infrastructure d'audit
  transversale instrumentée par chaque Manager. Importé par ~tous.
- **Ged** — stockage documentaire. Importé par 6 modules métier
  (Billing, Ecommerce, Editorial, Erp, Photo, Project) comme backend de
  fichiers. Confirme l'hypothèse « Ged = core » du workplan.

Ces 4 forment un **cluster mutuellement dépendant** (Configuration ↔ Dev
↔ Platform ↔ Ged) → ils ne se splittent pas entre eux, ils partent
ensemble dans `aurora-core`.

**SPÉCIAL — `General`** : pas d'entité propre. C'est le **shell
applicatif** (Dashboard, Profile, Search, Overview). Ses widgets/recherche
agrègent TOUS les modules métier → il dépend de tout. Il **ne peut pas**
être un package leaf installable seul. Options (à trancher en Gate 1) :
1. le garder dans `aurora-core` avec des intégrations **gardées** (widget
   Billing actif seulement si Billing installé) ;
2. le repousser **côté client** (chaque app compose son dashboard).
   → Recommandation provisoire : **option 1**, le shell est une commodité
   core, les widgets deviennent optionnels via les Context `isXEnabled()`.

**LEAF métier** (ne dépendent que du core → split trivial) : **Hr, Notes,
PersonalFinance, Planning, Tools** (+ Assistant sous réserve du lien
General). Ce sont les **premiers candidats au POC/rollout** — zéro arête
cross-business à découpler.

**MÉTIER tangled** : Billing, Crm, Ecommerce, Editorial, Erp, Photo,
Project — interconnectés. Détail et nature des couplages dans
`dependency_graph.md`. C'est l'objet du **Gate 1**.

## Notes pour la suite

- `ModuleParameterEnum` (66 cases, dans `Configuration/Setting/Enum/`)
  est **monolithique** : il énumère les toggles de tous les modules. Un
  split exige de le rendre extensible (chaque sous-bundle enregistre ses
  cases) — **blocker auto-discovery à traiter en Phase 2**.
- `tools/` à la racine (docker, php-cs-fixer, phpstan, rector,
  twig-cs-fixer) est de l'**outillage repo**, pas du code module — reste
  au niveau monorepo, non distribué.
- `migrations/` = **17 fichiers dans un seul dossier racine**, non
  partitionné par module → concern majeur de la **Phase 5**.
