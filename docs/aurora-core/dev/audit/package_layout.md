# Gate 1 — Layout des packages cibles (DÉCIDÉ : graphe en étoile)

> Livrable de **Gate 1**. **Décision (2026-05-30)** : on adopte la
> **stratégie de découplage en étoile** — chaque module distribuable ne
> dépend que d'`aurora-core`, zéro dépendance latérale. Le « comment » est
> dans **[`decoupling_strategy.md`](./decoupling_strategy.md)** (prérequis
> à exécuter AVANT tout split). Ce document fige la **liste cible**.

## Invariant

Un module métier n'importe que `aurora-core` (= `Core/` + Platform +
Configuration + Dev + Ged). Inséparable sémantiquement ⇒ **fusion**, pas
dépendance ni bridge. Cf. critère de vérification (grep) dans
`decoupling_strategy.md`.

## Liste cible des packages

| Package | Contenu | Dépend de | Note |
|---|---|---|---|
| **`axelraboit/aurora-core`** | `Core/` + Platform + Configuration + Dev + Ged + **General (shell)** + extension points (enums/events/registries/reference) | — | socle ; General reste ici mais agrège via registries (cat. C) |
| **`axelraboit/aurora-commerce`** | **Ecommerce + Erp** (fusionnés) | aurora-core | **seule fusion** (Product = mono-domaine, cat. E) |
| `axelraboit/aurora-crm` | Crm | aurora-core | autonome après cat. B/D |
| `axelraboit/aurora-billing` | Billing | aurora-core | soft-ref vers Crm (cat. D) |
| `axelraboit/aurora-editorial` | Editorial | aurora-core | block registry (cat. C) |
| `axelraboit/aurora-photo` | Photo | aurora-core | soft-ref vers Crm (cat. D) |
| `axelraboit/aurora-project` | Project | aurora-core | soft-ref Crm + event Billing (cat. D) |
| `axelraboit/aurora-hr` | Hr | aurora-core | **leaf pur** |
| `axelraboit/aurora-notes` | Notes | aurora-core | **leaf pur** |
| `axelraboit/aurora-personal-finance` | PersonalFinance | aurora-core | **leaf pur** |
| `axelraboit/aurora-planning` | Planning | aurora-core | **leaf pur** |
| `axelraboit/aurora-tools` | Tools | aurora-core | **leaf pur** |
| `axelraboit/aurora-assistant` | Assistant | aurora-core | leaf (sous réserve lien General) |

**13 packages** (1 core + 12 modules), tous en étoile. Aucun `require`
inter-module.

## Comparé aux options abandonnées

- ❌ **Bridges** (~12 packages dont 5 bridges) : abandonné — dette de
  maintenance, granularité artificielle.
- ❌ **Fusion large `aurora-commerce` = Ecommerce+Erp+Crm+Billing** :
  abandonné — Crm/Billing sont découplables (cat. B/D), pas besoin de les
  noyer. On ne fusionne QUE le vrai mono-domaine (Ecommerce+Erp).

## Sort de `General`

**Décidé** : reste dans `aurora-core` comme shell applicatif, mais ses
agrégations (Dashboard widgets, Search) sont **inversées via registries
core** (cat. C de `decoupling_strategy.md`) pour ne plus importer aucun
module métier — sinon core dépendrait des modules (inversion interdite).

## Ordre d'extraction (rollout J5, après découplage)

Du plus isolé au plus couplé :

1. **Leaves purs** : Tools, Hr, Planning, Notes, PersonalFinance, Assistant.
2. **Soft-ref / registry** : Photo, Editorial, Billing, Crm, Project.
3. **Fusion** : Commerce (Ecommerce+Erp) en dernier.

## POC (J3)

Cible : un **leaf pur** (`aurora-tools` ou `aurora-hr`) pour valider la
mécanique splitsh + sous-bundle sans couplage. Puis un **second POC sur la
cat. D** (soft-ref, p.ex. Photo→Crm) car c'est le risque #1.

## Décision

- Layout retenu : **graphe en étoile, 13 packages** ✅
- Stratégie : **découplage-first** (cf. `decoupling_strategy.md`) ✅
- POC target : **leaf pur** (Tools/Hr) puis **cat. D** ✅
- Sort de `General` : **core + registries** ✅
- Arbitre / date : Axel, 2026-05-30 ✅
