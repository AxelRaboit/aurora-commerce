# Plan de chantier — Split aurora-core en monorepo

## Objectif et documents liés

Ce document orchestre l'exécution complète du split d'`axelraboit/aurora`
en monorepo de N packages Composer. Il s'appuie sur deux audits qui
doivent être lancés comme prérequis :

- [`audit_monorepo_split.md`](./audit_monorepo_split.md) — audit
  aurora-core (9 phases, ~50 checkpoints).
- [`audit_monorepo_split_client.md`](./audit_monorepo_split_client.md) —
  audit aurora-client (11 phases, ~30 checkpoints).

Ce plan définit **l'ordre d'attaque**, les **points de synchronisation**
entre les deux audits, les **gates de décision** Go/No-Go, et la
**roadmap d'exécution** une fois la décision prise.

> **Public visé** : sessions Claude Code successives. Chaque jalon de ce
> plan peut être donné comme goal à une session distincte. Les sessions
> annexes (POCs, extractions module par module) peuvent tourner en
> parallèle de la session principale.

---

## Vue d'ensemble (timeline indicative)

| Jalon | Phase | Durée estimée | Bloquant pour |
|---|---|---|---|
| J0 | Préparation | 1-2 j | Tout |
| J1 | Cartographie commune | 5-10 j | Toute la suite |
| **🚦 Gate 1** | **Décision groupings** | 1 j | Audit technique |
| J2 | Audit technique parallélisé | 10-15 j (parallel) | POC |
| **🚦 Gate 2** | **Décision stratégie assets** | 2 j | POC |
| J3 | POC end-to-end (Billing) | 10-15 j | Décision finale |
| **🚦 Gate 3** | **Go / No-Go final** | 1 j | Rollout |
| J4 | Planification rollout | 3 j | Exécution |
| J5 | Exécution rollout | 4-8 sem | Bascule |
| J6 | Bascule officielle | 1 sem | — |

**Total estimé** : 3 à 4 mois calendaires, en sequential, avec quelques
phases parallélisables.

---

## J0 — Préparation

**Goal** : poser le décor pour que les audits et POCs se déroulent
proprement.

- [ ] **Créer la branche** `feat/monorepo-audit` sur aurora-core.
- [ ] **Créer un dossier `docs/aurora-core/dev/audit/`** où tous les
  livrables intermédiaires des audits seront posés (graphes, matrices,
  comparatifs).
- [ ] **Cloner aurora-client localement** en parallèle d'aurora-core
  (chemin attendu : `../aurora-client/`).
- [ ] **Vérifier l'accès** aux outils candidats : `splitsh/lite` (binaire
  ou Docker), `symplify/monorepo-builder` (Composer dev dependency).
- [ ] **Snapshot des métriques actuelles** : nombre d'entités, lignes
  de code par module, dépendances Composer, taille du build Vite.
  Ces métriques serviront de baseline pour mesurer les régressions.
- [ ] **Tag de safety net** : `git tag pre-monorepo-audit` sur aurora-core
  et aurora-client (rollback facile).

---

## J1 — Cartographie commune (bloquant)

**Goal** : exécuter `audit_monorepo_split.md` Phase 1 dans son intégralité.
C'est la seule phase qui doit être terminée avant tout le reste — elle
conditionne le reste du chantier.

**Livrables attendus à la fin de J1** :

- [ ] **Tableau modules × statut** (core / métier / à débattre) avec
  justification.
- [ ] **Graphe Mermaid des dépendances inter-modules** posé dans
  `docs/aurora-core/dev/audit/dependency_graph.md`.
- [ ] **Tableau des couplages à `AuroraBundle.php`** avec recommandation
  de distribution.

**Comment** :

```
Goal pour Claude Code session :
"Exécute Phase 1 (1.1, 1.2, 1.3) de
docs/aurora-core/dev/audit_monorepo_split.md. Pose tous les livrables
dans docs/aurora-core/dev/audit/. Ne touche pas aux Phases 2+."
```

---

## 🚦 Gate 1 — Décision sur les groupings de modules

**Question à trancher** : à la lecture du graphe de dépendances, certains
modules doivent-ils être **fusionnés** en un seul package au lieu d'être
splittés individuellement ?

**Pattern de décision** (cf. réponse à la question « si modules dépendent
d'autres ») :

| Cas | Décision |
|---|---|
| Dépendance unilatérale légère (A → B) | Split, A déclare B en require |
| Dépendance circulaire (A ↔ B) | **Fusionner** A+B dans un seul package, OU extraire un `aurora-X-shared` plus bas |
| Dépendance bidirectionnelle de fait (sans cycle direct mais l'un n'a aucun sens sans l'autre) | **Fusionner** |
| Module isolé (Photo, peut-être Billing) | Split sans problème |

**Livrable** : `docs/aurora-core/dev/audit/package_layout.md` qui fige
la liste définitive des packages cibles, p.ex. :

```
- axelraboit/aurora-core            (Core + User + Configuration + Administration + Auth + Dashboard + Ged)
- axelraboit/aurora-billing         (autonome)
- axelraboit/aurora-commerce        (Ecommerce + Crm fusionnés, si cycle détecté)
- axelraboit/aurora-editorial       (autonome)
- axelraboit/aurora-erp             (autonome)
- axelraboit/aurora-photo           (autonome)
- axelraboit/aurora-project         (autonome)
```

Cette liste sert de référence pour toute la suite.

---

## J2 — Audit technique parallélisé

**Goal** : compléter les audits sur les phases qui ne dépendent plus
que de la cartographie. Lancer deux tracks en parallèle.

### Track A — aurora-core (session Claude #1)

- [ ] `audit_monorepo_split.md` **Phase 2** (auto-discovery : Doctrine,
  Twig, traductions, routes, services, permissions).
- [ ] `audit_monorepo_split.md` **Phase 4** (tests).
- [ ] `audit_monorepo_split.md` **Phase 5** (migrations Doctrine).
- [ ] `audit_monorepo_split.md` **Phase 6** (mémoires + docs).
- [ ] `audit_monorepo_split.md` **Phase 7** (outillage monorepo — choix
  splitsh vs symplify, composer.json templates, CI/CD).

### Track B — aurora-client (session Claude #2)

- [ ] `audit_monorepo_split_client.md` **Phase 1** (cartographie client).
- [ ] `audit_monorepo_split_client.md` **Phase 2** (composer.json).
- [ ] `audit_monorepo_split_client.md` **Phase 3** (bundles.php).
- [ ] `audit_monorepo_split_client.md` **Phase 4** (resolve_target_entities).
- [ ] `audit_monorepo_split_client.md` **Phase 6** (Module Toggle dashboard).
- [ ] `audit_monorepo_split_client.md` **Phase 7** (showcase modules).
- [ ] `audit_monorepo_split_client.md` **Phase 10** (CI/CD client).

### Track C — Phase 3 du premier audit (assets Vue) — DÉLIBÉRÉMENT EN PARALLÈLE MAIS À PART

C'est la phase la plus risquée et conditionne tout. Lui dédier une
session entière :

- [ ] `audit_monorepo_split.md` **Phase 3** (3.1, 3.2, 3.3 — inventaire
  + build pipeline + aliases).
- [ ] **NE PAS** faire Phase 3.4 (POC des 3 options) — c'est le Gate 2.

### Synchronisation fin de J2

- [ ] **Point de revue** : les 3 tracks remontent leurs livrables.
- [ ] **Identifier les contradictions** : si Track A et Track B
  recommandent des approches incompatibles, trancher avant le POC.
- [ ] **Mettre à jour** les deux audits si la cartographie a fait
  évoluer la structure des phases.

---

## 🚦 Gate 2 — Décision sur la stratégie assets Vue

**Question à trancher** : laquelle des 3 options assets retenir ?

- [ ] **Mini-POC en parallèle des 3 options** (audit Phase 3.4) sur le
  module Billing seul, **sans** faire le split complet.
- [ ] **Matrice de décision** : pour chaque option, scorer sur :
  - Préservation HMR
  - Customization possible côté client
  - Complexité du `vite.config.js` client
  - Robustesse en prod
  - Effort d'implémentation
- [ ] **Décision posée** dans
  `docs/aurora-core/dev/audit/assets_strategy_decision.md`.

**🛑 Si aucune option n'est satisfaisante** : envisager de **rester
mono-package** et améliorer le système de Module Toggle existant pour
réduire l'install size par tree-shaking / lazy-loading côté Vite. Le
chantier monorepo s'arrête ici.

**✅ Si une option est retenue** : passer à J3.

---

## J3 — POC end-to-end (Billing)

**Goal** : implémenter le split complet sur **un seul module** (Billing
recommandé, ou autre selon Gate 1) et valider tous les critères de
succès Phase 9.3 de `audit_monorepo_split.md`.

### Tâches

- [ ] Configurer **splitsh/lite** (ou outil retenu) pour produire
  `axelraboit/aurora-billing` à partir de `src/Module/Billing/` + assets
  + tests + traductions + docs + mémoires associés.
- [ ] Créer le `AuroraBillingBundle.php` qui enregistre Doctrine
  mappings, Twig, routes, traductions, ModuleToggle pour Billing seul.
- [ ] Créer le `composer.json` du sous-package.
- [ ] Premier split → créer le repo `aurora-billing` sur GitHub.
- [ ] Côté aurora-client : appliquer le diff de `composer.json` +
  `bundles.php` + `vite.config.js` selon les conclusions des audits.
- [ ] Lancer toute la suite de validation :
  - [ ] `php bin/phpunit` côté aurora-core (sans Billing) → vert
  - [ ] `php bin/phpunit` côté aurora-billing → vert
  - [ ] `php bin/phpunit` côté aurora-client → vert
  - [ ] `php bin/console doctrine:schema:validate` avec et sans Billing
  - [ ] `php bin/console debug:router` montre/cache les routes Billing
  - [ ] Build Vite OK (selon l'option retenue Gate 2)
  - [ ] Extension Sylius-style depuis le client fonctionne
    (étendre `BillingInvoice`, override le manager)
  - [ ] Module Toggle dashboard fonctionne avec Billing installé/non
- [ ] **Mesure des régressions** : comparer aux métriques baseline J0
  (build time, install time, taille bundle JS).

### Livrable

- [ ] Rapport `docs/aurora-core/dev/audit/poc_billing_report.md` :
  - Critères qui passent ✅
  - Critères qui échouent ❌ (avec analyse de la cause)
  - Effort réel vs estimation
  - Recommandations pour les modules suivants

---

## 🚦 Gate 3 — Go / No-Go final

**Question à trancher** : on lance le rollout complet ?

| Si | Alors |
|---|---|
| Tous les critères Phase 9.3 passent + effort raisonnable | **Go** → J4 |
| Quelques critères échouent mais corrigeables | **Go conditionnel** → fixer puis J4 |
| Critère bloquant (extension Sylius cassée, build Vite instable) | **No-Go** → repenser ou abandonner |
| Effort × N modules >> budget | **Go partiel** → splitter seulement les modules les plus indépendants, garder les autres groupés |

- [ ] Décision posée dans
  `docs/aurora-core/dev/audit/go_no_go_decision.md` avec raisonnement
  argumenté.
- [ ] Si Go : passer à J4. Si No-Go : merger les apprentissages dans la
  doc et fermer le chantier proprement.

---

## J4 — Planification rollout

**Goal** : finaliser l'ordre d'extraction et la stratégie de migration
pour les clients existants.

- [ ] **Ordre d'extraction** : du module le plus isolé (graphe Phase 1.2)
  au plus connecté. Recommandation : Photo > Billing > Editorial >
  Project > Erp > Crm > Ecommerce (ordre indicatif, à ajuster selon
  le graphe).
- [ ] **Stratégie de transition clients** (cf.
  `audit_monorepo_split_client.md` Phase 11) :
  - Option A — Hard cut majeur v2.0
  - Option B — Méta-package permanent
  - Option C — Méta-package deprecated 1-2 versions puis Hard cut
  - Recommandation provisoire : **Option C**.
- [ ] **Guide de migration** :
  `docs/aurora-core/dev/migrating_to_monorepo.md` (squelette).
- [ ] **Communication** : changelog, message Slack/email aux consommateurs
  connus, banner sur le README.
- [ ] **Updates skills** : adapter les templates Claude
  (`add-module`, `add-entity`, `extend-aurora-entity`, etc.) pour la
  nouvelle topologie.

---

## J5 — Exécution rollout (4-8 semaines)

**Goal** : extraire les modules un par un, dans l'ordre J4, en suivant
le playbook validé au POC.

Pour **chaque module** :

- [ ] Reprendre le pattern POC Billing.
- [ ] Splitter via splitsh.
- [ ] Créer le repo GitHub enfant.
- [ ] Mettre à jour le méta-package (s'il existe) pour pointer le
  sous-package.
- [ ] Tester côté aurora-client (template) et sur 1 client réel si
  possible.
- [ ] **Tag + release** du sous-package.
- [ ] **Commit dans aurora-core** : marquer le module comme « extrait
  vers `axelraboit/aurora-<x>`, voir CHANGELOG ».
- [ ] **Mettre à jour le tableau de suivi** dans
  `docs/aurora-core/dev/audit/rollout_status.md` (un row par module
  avec statut : pending / in-progress / done).

**Rythme attendu** : 1 module / semaine en moyenne. Plus rapide pour les
isolés (Photo, Billing), plus lent pour les connectés (Crm, Ecommerce).

**Garde-fous pendant le rollout** :

- [ ] **Tests verts** sur aurora-core et aurora-client à chaque module
  extrait.
- [ ] **Aucun client existant cassé** entre deux extractions (si
  méta-package).
- [ ] **Rollback possible** à n'importe quel module — chaque extraction
  est un commit/tag révoquable.

---

## J6 — Bascule officielle

**Goal** : marquer la fin du split avec une release majeure.

- [ ] **Release v2.0** d'`axelraboit/aurora-core` (ou nom retenu).
- [ ] **Release v2.0** de tous les sous-packages (versioning synchronisé).
- [ ] **Méta-package** `axelraboit/aurora` :
  - Si Option C : marqué `deprecated`, require tous les sous-packages
    en v2.0.
  - Si Option A : archivé, lien vers le guide de migration.
- [ ] **Publication du guide de migration** dans les docs et le README.
- [ ] **Mise à jour de tous les docs**
  (`docs/aurora-core/dev/extending_aurora.md`,
  `entity_extensibility_convention.md`,
  `extracting_a_module.md`, etc.) pour refléter la nouvelle topologie.
- [ ] **Mise à jour des mémoires** : créer
  `.claude/memory/aurora-core/decisions/decision_monorepo_split.md`
  qui documente le raisonnement, le verdict, et la roadmap suivie. Lier
  depuis l'index.
- [ ] **Annonce** : changelog public, communication aux consommateurs
  connus.

---

## Synthèse : comment donner ce plan à Claude Code

Le plan ci-dessus est conçu pour être consommé **jalon par jalon**, pas
en bloc. Pour chaque jalon, créer un goal du type :

```
Goal Claude Code :
"Exécute le jalon Jx (titre) du plan
docs/aurora-core/dev/monorepo_split_workplan.md. Lis les audits liés
si pertinent. Pose les livrables attendus. Si tu identifies une raison
de réviser le plan, propose-la avant d'exécuter."
```

**Sessions parallélisables** :

- J2 Track A, Track B, Track C peuvent tourner en 3 sessions parallèles
  (3 instances Claude Code).
- J5 (rollout) peut paralléliser 2-3 modules en parallèle si plusieurs
  devs/sessions disponibles, à condition que les modules choisis ne se
  touchent pas dans le graphe de dépendances.

**Sessions séquentielles obligatoires** :

- J1 → J2 (la cartographie bloque tout)
- Tous les gates (1, 2, 3) sont séquentiels
- J6 (bascule) ne peut commencer qu'après J5 complet

---

## Risques majeurs et plans de mitigation

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| Aucune option assets Vue ne fonctionne | Moyenne | Critique | Gate 2 décide tôt → No-Go propre avant trop d'effort |
| Dépendances inter-modules plus tangled qu'attendu | Moyenne | Élevé | Gate 1 ajuste les groupings → moins de packages mais cohérent |
| Client existant ne peut pas migrer | Élevée | Moyen | Option C (méta-package deprecated) donne 6-12 mois de transition |
| Skills Claude cassés pendant le rollout | Élevée | Faible | J4 met à jour les skills AVANT le rollout, pas après |
| Tests qui passent en monolithe mais pas en split | Élevée | Moyen | POC Billing les détecte tôt, on les fixe avant J5 |
| Régression de perf build Vite | Faible | Moyen | Métriques baseline J0 + comparaison à chaque jalon |
| Abandon du chantier en milieu de rollout | Faible | Élevé | Méta-package + rollback Git permettent de revenir à monolithe à tout moment |

---

## Effort total estimé

- **Audit + Gates + POC** : ~30 j-h (avec parallélisation des tracks)
- **Rollout** : 7 modules × ~5 j-h = ~35 j-h
- **Bascule + doc + skills** : ~10 j-h
- **Total** : **~75 j-h** (~3-4 mois calendaires en sequential, ~2 mois
  en parallel avec ressources adéquates)

Ces chiffres sont indicatifs et seront raffinés à chaque gate.
