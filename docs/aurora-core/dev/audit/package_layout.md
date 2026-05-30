# Gate 1 — Layout des packages cibles (DRAFT, décision en attente)

> Livrable de **Gate 1**. Fige (à terme) la liste définitive des packages.
> **Statut : DRAFT** — propose 3 options, attend l'arbitrage user.
> Base : `module_inventory.md` + `dependency_graph.md` (arêtes lourdes
> auditées).

## Acquis (non débattus)

- **`axelraboit/aurora-core`** = `Core/` + **Platform** + **Configuration**
  + **Dev** + **Ged**. Cluster socle mutuellement dépendant. Non négociable.
- **Leaves métier** = **Hr, Notes, PersonalFinance, Planning, Tools**
  (+ Assistant sous réserve lien General). **Aucune** arête cross-business
  → packages autonomes triviaux : `aurora-hr`, `aurora-notes`,
  `aurora-personal-finance`, `aurora-planning`, `aurora-tools`,
  `aurora-assistant`. **Le gain « aurora léger » est gratuit ici.**
- **Pré-requis core** (avant tout split) : `AbstractAuroraModuleBundle`
  + `ModuleParameterEnum` extensible + déplacer `CurrencyEnum` (et autres
  value-enums transverses) vers core.

## Le nœud : le cluster commerce/PM

7 modules entrelacés : Billing, Crm, Ecommerce, Editorial, Erp, Photo,
Project. Chaîne de dépendance de fait :

```
Project → Crm → Ecommerce → Erp        (+ Project → Billing → {Crm, Erp})
Photo  → Crm                            Editorial → Ecommerce / Crm
```

Avec des `require` purs, **installer Project tire Crm+Ecommerce+Erp+
Billing+Editorial** → l'objectif « léger » s'effondre POUR CE CLUSTER.
Mais la plupart des arêtes sont des **intégrations optionnelles** (1 classe,
1 listener, 1 lien entité nullable) → extractibles en bridges.

## Trois options pour le cluster

### Option A — Bridges (granularité max)

Chaque module = 1 package autonome. Les intégrations cross-business
partent dans des **bridges** dédiés que le client installe à la carte :

```
aurora-billing, aurora-crm, aurora-ecommerce*, aurora-erp,
aurora-editorial, aurora-photo, aurora-project
+ aurora-project-billing   (ProjectInvoiceManager)
+ aurora-billing-crm       (Tiers↔Company)
+ aurora-crm-ecommerce     (OrderCrmSyncListener)
+ aurora-editorial-ecommerce (BlocksRenderer listing embed)
+ aurora-project-crm       (liens Company/Contact/Deal)  — ou require
* aurora-ecommerce require aurora-erp (couplage dur Product, inévitable)
```

- ✅ « léger » maximal ; un client CMS-only = `aurora-editorial` seul.
- ✅ chaque intégration opt-in explicite.
- ❌ **~7 + 5 bridges = 12 packages** pour le cluster ; refactor des
  interfaces pour tolérer la cible absente (le plus de travail).

### Option B — Fusion pragmatique (`aurora-commerce`)

Fusionner les 4 plus entrelacés en un package :

```
aurora-commerce  = Ecommerce + Erp + Crm + Billing
aurora-editorial (require commerce si bloc listing gardé soft)
aurora-photo     (require crm → ou guard)
aurora-project   (require commerce)
```

- ✅ **4 packages** au lieu de 12 ; zéro refactor des couplages internes.
- ✅ cohérent métier (« suite commerce »).
- ❌ grain grossier : un client qui veut juste la facturation tire tout
  le commerce. Va à l'encontre du « léger » pour ces modules.

### Option C — Hybride (reco) : leaves now, cluster defer

- **Phase 1 (maintenant)** : splitter SEULEMENT le core + les leaves
  (Hr, Notes, PersonalFinance, Planning, Tools, Photo si Crm-link gardable,
  Assistant). C'est ~50 % des modules, gain « léger » immédiat, **zéro
  découplage cross-business**.
- **Phase 2 (plus tard)** : le cluster commerce reste **groupé dans core
  OU dans un `aurora-commerce`** temporaire, et on tranche bridges-vs-fusion
  une fois la mécanique de split rodée sur les leaves.

- ✅ livre de la valeur vite, dérisque (POC sur un vrai leaf), repousse la
  partie dure.
- ✅ compatible avec l'esprit du workplan (rollout incrémental).
- ❌ le cluster commerce reste lourd entre-temps.

## Recommandation

**Option C (hybride)** comme stratégie de rollout, avec **Option A
(bridges)** comme cible finale pour le cluster commerce — Option B en
repli si les bridges s'avèrent trop coûteux en J2.

POC (J3) : un **leaf petit** (`aurora-tools` ou `aurora-hr`), PAS Billing,
pour valider la mécanique d'extraction sans buter sur le découplage.

## Décision (à remplir)

- Layout retenu : _______
- POC target : _______
- Sort de `General` : _______
- Date / arbitre : _______
