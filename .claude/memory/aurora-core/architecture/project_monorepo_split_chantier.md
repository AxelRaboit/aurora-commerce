# Chantier — Split aurora-core en monorepo de N packages Composer

## Règle

**Chantier exploratoire en cours (2026-05-30)** : étudier la transformation
d'`axelraboit/aurora` (mono-package actuel) en **monorepo Composer**
publiant 1 package core (`aurora-core` = Core + User + Configuration +
Administration + Auth + Dashboard + Ged) + N packages module
(`aurora-billing`, `aurora-crm`, `aurora-ecommerce`, `aurora-editorial`,
`aurora-erp`, `aurora-photo`, `aurora-project`).

Le développement reste mono-codebase ; seule la distribution Composer est
splittée. Pattern Symfony / Doctrine / Sylius / Laravel.

**Statut** : en phase d'audit. Aucun code touché. 3 documents d'audit et
de planification posés, à consommer par des sessions Claude Code futures.

## Pourquoi

- Le user veut pouvoir proposer un **aurora "léger"** : un client qui ne
  fait que du CMS n'a pas besoin d'embarquer Billing/CRM/Ecommerce/ERP.
- Le système de **module toggles** existant cache l'UI mais n'allège pas
  l'install (vendor reste plein de code mort). Composer sub-packages
  résout cette dimension-là.
- À l'inverse, un fork multi-repos = piège classique (double maintenance,
  divergence, double CI). Le monorepo + split automatique (`splitsh/lite`)
  donne le meilleur des deux mondes.

## Comment l'appliquer

**Trois documents structurent le chantier** (tous dans
`docs/aurora-core/dev/`) :

1. **[`audit_monorepo_split.md`](../../../../docs/aurora-core/dev/audit_monorepo_split.md)** —
   9 phases d'audit côté aurora-core (cartographie modules, auto-discovery
   Doctrine/Twig/routes, assets Vue avec 3 options A/B/C, tests,
   migrations, mémoires, outillage splitsh, impact skills, POC Billing).
2. **[`audit_monorepo_split_client.md`](../../../../docs/aurora-core/dev/audit_monorepo_split_client.md)** —
   11 phases d'audit côté aurora-client (composer.json, bundles.php,
   resolve_target_entities, pipeline Vite, Module Toggle dashboard,
   showcase modules, docs, skills, CI/CD, migration des clients
   existants).
3. **[`monorepo_split_workplan.md`](../../../../docs/aurora-core/dev/monorepo_split_workplan.md)** —
   Plan de chantier qui orchestre les deux audits en 7 jalons (J0 → J6)
   avec 3 gates de décision (groupings modules, stratégie assets, Go/No-Go
   final). Effort total estimé : ~75 j-h, ~2-4 mois.

**Pour une session Claude Code future** : ouvrir le workplan, identifier
le jalon courant via `docs/aurora-core/dev/audit/` (livrables
intermédiaires), exécuter le jalon, poser ses livrables, mettre à jour
cette mémoire avec l'état.

**Points critiques à garder en tête** :

- **Gate 2 (assets Vue)** est le point d'incertitude maximale. Si aucune
  des 3 options ne convainc (A pré-buildé / B plugin Vite custom / C
  symlinks post-install), le chantier s'arrête là — on reste mono-package
  et on améliore les Module Toggles.
- **Dépendances inter-modules** (Phase 1.2 audit core) peuvent forcer
  des **groupings** (ex: si Billing ↔ CRM en cycle, fusionner en
  `aurora-commerce`). La cartographie conditionne la liste finale des
  packages.
- **Stratégie de migration clients existants** : option recommandée =
  méta-package `axelraboit/aurora` deprecated pendant 1-2 versions, puis
  hard cut v2.0 (pattern Symfony).

## État du chantier

| Date | Jalon | État |
|---|---|---|
| 2026-05-30 | J0 — Préparation | À démarrer |
| — | J1 — Cartographie commune | Bloqué (J0) |
| — | Gate 1 — Décision groupings | Bloqué (J1) |
| — | J2 — Audit technique parallélisé | Bloqué (Gate 1) |
| — | Gate 2 — Décision stratégie assets | Bloqué (J2) |
| — | J3 — POC end-to-end (Billing) | Bloqué (Gate 2) |
| — | Gate 3 — Go / No-Go final | Bloqué (J3) |
| — | J4 — Planification rollout | Bloqué (Gate 3) |
| — | J5 — Exécution rollout | Bloqué (J4) |
| — | J6 — Bascule officielle | Bloqué (J5) |

**Convention de suivi** : mettre à jour cette table à chaque jalon
franchi. Si un gate revient en No-Go, fermer le chantier proprement
(merger les apprentissages dans la doc, archiver l'audit) et tout l'effort
ne sera pas perdu — l'audit lui-même produit de la connaissance utile sur
la topologie modulaire d'Aurora.

## Liens

- [[pattern_core_submodules_split]] — la philosophie « 1 module = 1
  toggle root » qui sous-tend le découpage actuel et la motivation du
  split.
- [[decision_core_submodule_nesting]] — précédent breaking change
  (0.4.0) qui a déjà obligé les clients à migrer ; donne un précédent
  méthodologique pour gérer la transition v2.0.
- [`extracting_a_module.md`](../../../../docs/aurora-core/dev/extracting_a_module.md) —
  playbook complémentaire (spin-off d'un module vers un client dédié,
  pattern 1-to-1). À ne pas confondre avec le split monorepo.
