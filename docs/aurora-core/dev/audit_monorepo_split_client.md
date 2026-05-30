# Audit — Impact du split monorepo sur aurora-client

## Contexte

Cet audit est le **pendant côté consommateur** de
[`audit_monorepo_split.md`](./audit_monorepo_split.md). Le premier audite
ce qui doit changer dans `aurora-core` pour devenir un monorepo de N
packages Composer. Celui-ci audite ce qui doit changer dans
`aurora-client` (le template app Symfony qui consomme `axelraboit/aurora`)
pour s'adapter à cette nouvelle topologie.

**Hypothèse de travail** : après le split, aurora-core publie
`axelraboit/aurora-core` (dépendance dure) + N packages
`axelraboit/aurora-billing`, `aurora-crm`, etc. (optionnels). Un client
final fait :

```bash
composer require axelraboit/aurora-core axelraboit/aurora-billing
```

et n'embarque que ce qu'il utilise.

**Livrable de cet audit** : un rapport listant tous les points
d'aurora-client qui présupposent un seul package Composer aurora, avec
pour chacun la modification à apporter.

> Cet audit est **dépendant** de Phase 1 et Phase 3 de
> `audit_monorepo_split.md` (cartographie des modules + stratégie assets
> retenue). Sans ces deux phases tranchées, l'audit client reste
> théorique sur certains points.

---

## Phase 1 — Cartographie d'aurora-client

### 1.1 Inventaire du repo

- [ ] Cloner ou se positionner sur le repo `aurora-client` (template
  app Symfony consommatrice).
- [ ] Lister :
  - Tous les fichiers de config Symfony (`config/packages/*.yaml`,
    `config/services.yaml`, `bundles.php`)
  - Le `composer.json` racine
  - Le `package.json` + `vite.config.js` + `jsconfig.json`
  - Tous les `App\Module\*` éventuels (modules showcase comme Tracking)
  - Le `Makefile` + scripts custom
  - Les workflows CI (`.github/workflows/*.yml`)
- [ ] **Livrable** : arbo commentée avec, pour chaque entrée, un
  marqueur « impacté par le split » / « neutre ».

### 1.2 Points d'extension actuels

- [ ] Lister tous les `App\Module\*\Entity\*` qui étendent un
  `Aurora\Module\*\Entity\Abstract*` (extension Sylius-style).
- [ ] Lister les `App\Module\*\Manager\*` et `App\Module\*\Serializer\*`
  qui étendent ou décorent aurora.
- [ ] Lister les wrappers Vue qui consomment des composants aurora avec
  `extraFields`.
- [ ] Pour chaque extension, identifier de **quel package aurora** elle
  dépendra après split. C'est le critère principal pour mettre à jour
  `composer.json` du client.

---

## Phase 2 — `composer.json` client

### 2.1 État actuel

- [ ] Lire le `composer.json` actuel d'aurora-client.
- [ ] Identifier la ligne `axelraboit/aurora`.
- [ ] Lister les `extra.symfony.*` configs éventuelles.

### 2.2 Refonte post-split

- [ ] Remplacer la ligne unique par N lignes (une par package nécessaire).
- [ ] Définir une **stratégie par défaut** pour le template :
  - **Option A — Full install** : le template inclut tous les packages
    aurora pour montrer toutes les fonctionnalités. Le client retire
    ce dont il n'a pas besoin.
  - **Option B — Minimal core** : le template n'a que `aurora-core`,
    le client ajoute les modules qu'il veut.
  - **Option C — Profil métier** : le template propose plusieurs
    `composer.json` (`composer-cms.json`, `composer-erp.json`,
    `composer-photo.json`) qu'il faut copier au setup.
  - **Recommandation provisoire** : Option B (minimal core) + une
    checklist `setup.md` listant les modules disponibles avec
    « `composer require axelraboit/aurora-X` ».

### 2.3 Versioning

- [ ] Si aurora-core sync les versions entre packages (recommandé), tous
  les `require` du client doivent être sur la même contrainte
  (`^X.Y`). Documenter ce point.
- [ ] Concevoir une commande `make aurora-update` qui bump tous les
  packages en cohérence (évite les mises à jour partielles).

---

## Phase 3 — Bundles registration (`config/bundles.php`)

### 3.1 État actuel

- [ ] Lire `config/bundles.php` aurora-client.
- [ ] Repérer l'entrée `Aurora\AuroraBundle::class => [...]`.

### 3.2 Refonte post-split

- [ ] Chaque sous-package publie son propre bundle (`AuroraCoreBundle`,
  `AuroraBillingBundle`, …). Le client doit les enregistrer un par un :
  ```php
  Aurora\Core\AuroraCoreBundle::class => ['all' => true],
  Aurora\Billing\AuroraBillingBundle::class => ['all' => true],
  Aurora\Crm\AuroraCrmBundle::class => ['all' => true],
  // ...
  ```
- [ ] **Question** : peut-on auto-découvrir les bundles présents dans
  `vendor/axelraboit/aurora-*/` et les enregistrer dynamiquement ? Ça
  éviterait au client d'éditer `bundles.php` à chaque ajout/retrait de
  package. Symfony Flex recipe ? À investiguer.

---

## Phase 4 — `resolve_target_entities`

### 4.1 État actuel

- [ ] Lire `config/packages/doctrine.yaml` côté aurora-client.
- [ ] Repérer la section `resolve_target_entities` (qui mappe les
  interfaces aurora vers les concrete classes App\\ étendues côté
  client).

### 4.2 Refonte post-split

- [ ] **Sans changement structurel** : les entités étendues par le
  client restent dans `App\Module\*` et le mapping reste local au
  client. Aucune raison de splitter ça.
- [ ] **Risque** : si une entité bouge de namespace côté aurora (ex:
  `Aurora\Module\Billing\Entity` → `Aurora\Billing\Entity` à cause du
  split), le client doit mettre à jour les `targetEntity`.
  Documenter le mapping `avant → après` à publier dans le CHANGELOG
  d'aurora-core.

---

## Phase 5 — Pipeline assets Vite

C'est le point le plus impacté. Le résultat dépend de la stratégie
retenue Phase 3 de `audit_monorepo_split.md`.

### 5.1 État actuel

- [ ] Lire `vite.config.js` aurora-client.
- [ ] Lire `package.json` aurora-client.
- [ ] Lire `jsconfig.json` aurora-client.
- [ ] Identifier comment les sources aurora sont importées aujourd'hui :
  - Aliases `@aurora/...` pointant vers `vendor/axelraboit/aurora/...` ?
  - Glob qui scanne `vendor/axelraboit/aurora/src/Module/*/assets/` ?
  - Symlinks ?
- [ ] Identifier la commande de build (`make build`, `npm run build`,
  `pnpm build`) et son orchestration.

### 5.2 Refonte post-split

#### Si Option A (pré-buildé) retenue côté aurora-core

- [ ] Les sous-packages publient leurs assets compilés. Vite côté
  client ne touche plus aux sources aurora — il importe juste les
  bundles pré-buildés.
- [ ] Mais : perte de HMR sur les fichiers aurora, perte de
  customization (overrides Vue côté client deviennent impossibles).

#### Si Option B (plugin Vite custom) retenue

- [ ] Le plugin Vite doit être livré (par où ? `aurora-core` peut-il
  publier un plugin Vite via npm ? ou inclus dans `aurora-core` PHP
  package, chargé via require relatif depuis `vendor/` ?).
- [ ] Le plugin scanne `vendor/axelraboit/aurora-*/` au boot du build et
  enregistre dynamiquement les aliases.
- [ ] aurora-client doit installer le plugin et l'enregistrer dans son
  `vite.config.js`.

#### Si Option C (symlinks post-install) retenue

- [ ] Le hook `composer install` symlink vers un dossier local.
- [ ] Le `vite.config.js` client n'a quasiment pas à changer (il scanne
  un dossier connu).

- [ ] **Livrable** : pour l'option retenue, écrire le diff du
  `vite.config.js` client et tester sur le POC Phase 9 de
  `audit_monorepo_split.md`.

### 5.3 `aliases.js` côté client

- [ ] Si aurora-client utilise des aliases `@aurora/...`, vérifier ce
  qu'ils deviennent (`@aurora-core/...` + `@aurora-billing/...` ?
  ou un alias unifié résolu par le plugin Vite ?).
- [ ] Mettre à jour les imports dans `App\Module\*\assets\` du client.

---

## Phase 6 — Module Toggle dashboard

### 6.1 Question architecturale

Le **module toggle dashboard** existant (`/dev/dashboard/modules` côté
core) permet aujourd'hui d'activer/désactiver un module à runtime. Avec
le split en packages Composer :

- Un module **non installé via Composer** n'apparaît même pas dans le
  dashboard (rien à toggler).
- Un module **installé** peut toujours être désactivé via le dashboard
  (pour le cacher temporairement).

- [ ] Vérifier que le dashboard se base bien sur les bundles présents
  dans `bundles.php` (pas sur une liste hardcodée).
- [ ] Documenter clairement la distinction « installé via Composer »
  vs « activé via toggle ».

### 6.2 Audit existant

- [ ] Lancer le skill `audit-module-toggles` sur aurora-client après
  split pour identifier les gaps.

---

## Phase 7 — Showcase modules dans le template

aurora-client embarque actuellement un module showcase **Tracking** (et
peut-être d'autres) à des fins de démo / onboarding.

### 7.1 Audit

- [ ] Lister tous les `App\Module\*` côté aurora-client qui ne sont
  PAS des extensions mais des modules autonomes (showcase).
- [ ] Vérifier si ces showcase dépendent de modules aurora-core
  spécifiques (ex: Tracking dépend-il de Configuration ? de Ged ?).

### 7.2 Refonte

- [ ] Si Tracking dépend de modules optionnels post-split (ex: Ged
  pourrait être reclassé core, mais Configuration sûrement reste core
  → OK), pas de changement.
- [ ] Mettre à jour `aurora-client/docs/getting-started/setup.md`
  §« Checklist — retirer un module showcase » pour refléter la nouvelle
  topologie.

---

## Phase 8 — Documentation aurora-client

### 8.1 Pages impactées

À auditer et mettre à jour :

- [ ] `aurora-client/docs/getting-started/setup.md` — la commande
  `composer require` change.
- [ ] `aurora-client/docs/getting-started/*` — toute mention de
  `axelraboit/aurora` doit être contextualisée.
- [ ] `aurora-client/docs/deployment/*` — vérifier si les guides
  Apache/CI mentionnent aurora package.
- [ ] `docs/aurora-core/dev/extending_aurora.md` — la table « quel
  module touche quoi » doit refléter les packages.
- [ ] `docs/aurora-core/dev/extending_agency_pilot.md` — exemple
  d'extension Agency : Agency vit dans quel package post-split ?
  Mettre à jour les imports.

### 8.2 Nouveau guide

- [ ] Créer `aurora-client/docs/getting-started/choosing_modules.md` qui
  liste les packages disponibles, leurs dépendances, et propose des
  profils types (CMS, e-commerce, ERP, Photo, …).

---

## Phase 9 — Skills Claude impactés côté client

Mirror de Phase 8 de `audit_monorepo_split.md`, focalisé sur les skills
qui agissent côté aurora-client :

- [ ] `extend-aurora-entity` : génère du code qui étend une entité
  aurora. Doit résoudre **dans quel package** vit l'entité parente
  pour générer le bon `use` statement. Probablement via un manifest
  ou un grep dans `vendor/axelraboit/aurora-*/`.
- [ ] `add-crud-list-ui` : génère du Vue pour un CRUD. Pas d'impact
  package, mais doit savoir où sont les composants aurora à wrapper.

### 9.1 Mécanisme de résolution

- [ ] Concevoir un mécanisme pour que les skills puissent répondre à
  « quelle classe Aurora\Module\X\Entity\Y existe-t-elle ? et dans
  quel package ? ». Plus simple : grep dans `vendor/axelraboit/`.

---

## Phase 10 — CI/CD aurora-client

### 10.1 Audit

- [ ] Lire `.github/workflows/ci.yml` (ou équivalent) aurora-client.
- [ ] Repérer toute mention de `axelraboit/aurora`.
- [ ] Vérifier la gestion du PAT si aurora-core est privé
  (`AURORA_CORE_READ_TOKEN` mentionné dans
  `aurora-client/deployment/github_actions_ci.md`).

### 10.2 Adaptation

- [ ] Le PAT doit pouvoir lire **tous les repos enfants** post-split
  (aurora-billing, aurora-crm, …) — vérifier les permissions GitHub.
- [ ] Si Packagist gère tous les packages, le `composer install` standard
  fonctionne sans config particulière. Sinon, ajouter N entrées
  `repositories` dans `composer.json` (sale, à éviter).

---

## Phase 11 — Migration des clients existants

aurora-client n'est pas le seul consommateur d'aurora ; des clients
réels (apps Symfony en prod) sont déjà installés sur `axelraboit/aurora`
monolithique. Le split est un **breaking change** pour eux.

### 11.1 Audit du nombre de clients

- [ ] Recenser les clients existants connus.
- [ ] Identifier ceux qui peuvent migrer immédiatement vs ceux qui ne
  peuvent pas (figés sur une version).

### 11.2 Stratégie de transition

Trois options :

- **Option A — Hard cut** : un tag majeur (v2.0 ?) introduit le split,
  pas de rétrocompat. Les clients migrent au moment de leur prochaine
  mise à jour majeure.
- **Option B — Méta-package** : on garde `axelraboit/aurora` comme
  méta-package qui require tous les sous-packages. Les clients
  existants restent fonctionnels sans rien changer. Les nouveaux clients
  utilisent les sous-packages directement.
- **Option C — Deprecation period** : le méta-package existe mais est
  marqué deprecated, avec un message expliquant comment migrer.

- [ ] **Recommandation provisoire** : Option C (méta-package deprecated)
  pour 1-2 versions, puis Option A. C'est le pattern Symfony.
- [ ] Documenter un **guide de migration** :
  `docs/aurora-core/dev/migrating_to_monorepo.md`.

---

## Livrable final de l'audit client

Un document de synthèse
`aurora-client/docs/getting-started/monorepo_migration_plan.md`
contenant :

- [ ] **Diff du `composer.json`** template (avant/après).
- [ ] **Diff du `bundles.php`** (avant/après).
- [ ] **Diff du `vite.config.js`** (avant/après, selon l'option assets
  retenue).
- [ ] **Liste des skills à adapter** côté client.
- [ ] **Guide de migration** pour les clients existants.
- [ ] **Profils types** (CMS / ERP / Photo / …) avec leur set de
  packages.

---

## Notes pour l'auditeur (Claude Code)

- Cet audit est **dépendant** de `audit_monorepo_split.md` Phase 1 et 3.
  Sans le graphe de dépendances et la stratégie assets retenue, plusieurs
  sections sont théoriques.
- Si les deux audits sont menés en parallèle (deux sessions), prévoir un
  point de synchronisation après la Phase 1 (cartographie) de
  `audit_monorepo_split.md`.
- Le POC monorepo (Phase 9 du premier audit) doit inclure un test
  d'install côté aurora-client avant d'être déclaré « succès ». C'est ce
  qui valide réellement que tout le pipeline fonctionne bout en bout.
- Côté méthode : commencer par cloner aurora-client localement et lire
  tous les fichiers de config. Beaucoup de réponses sont là, pas dans
  ce document.
