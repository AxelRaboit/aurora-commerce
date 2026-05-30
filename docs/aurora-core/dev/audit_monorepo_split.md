# Audit — Split aurora-core en monorepo + sous-packages Composer

> **⚠️ MàJ post-J1 (2026-05-30)** : la cartographie réelle donne **18
> modules** (pas 7), et User/Auth/Agency/Service vivent regroupés sous
> **Platform**. Le **Gate 1 a tranché** pour un **graphe en étoile** (aucune
> dépendance latérale ; cf. [`audit/decoupling_strategy.md`](./audit/decoupling_strategy.md)
> + [`audit/package_layout.md`](./audit/package_layout.md)). Les mentions
> « Billing POC » et « require entre sous-packages » ci-dessous sont
> **caduques** : le POC vise un **leaf pur** (Tools/Hr) après le pass de
> découplage, et il n'y a **aucun** require inter-module (sauf fusion
> Ecommerce+Erp → `aurora-commerce`). Livrables J1 dans `audit/`.

## Contexte et objectif

Transformer `axelraboit/aurora` (mono-package Composer actuel) en **monorepo**
qui publie automatiquement :

- 1 package **core** (`axelraboit/aurora-core`) : `src/Core/` + modules
  noyau non-débrayables (User, Configuration, Administration, Auth, Dashboard,
  Ged, et possiblement d'autres à statuer).
- N packages **module** indépendants (`axelraboit/aurora-billing`,
  `aurora-crm`, `aurora-ecommerce`, `aurora-editorial`, `aurora-erp`,
  `aurora-photo`, `aurora-project`, …) que les clients tirent à la carte.

Le **développement** reste mono-codebase (un seul repo, une seule CI), mais
Composer voit N packages publiables séparément. C'est le pattern Symfony,
Doctrine, Sylius, Laravel.

> **À ne pas confondre avec** `extracting_a_module.md` : ce dernier documente
> le spin-off d'un module vers un projet client dédié (1-to-1). Ici on garde
> la philosophie « bundle réutilisable », on découpe juste la distribution
> Composer.

**Outil pressenti** : `splitsh/lite` (utilisé par Symfony — split git history
par sous-dossier, rapide, sans dépendance PHP). Alternative à évaluer :
`symplify/monorepo-builder` (utilisé par Sylius, plus de fonctionnalités
mais plus lourd).

**Livrable de cet audit** : un rapport de synthèse listant tous les couplages
actuels qui empêchent un split propre, avec une recommandation de
découplage et un effort estimé pour chacun. Le rapport doit permettre de
décider si on lance le chantier complet et dans quel ordre.

---

## Phase 1 — Cartographie

### 1.1 Inventaire des modules

- [ ] Lister tous les dossiers `src/Module/*` avec, pour chacun : nb
  d'entités, nb de controllers, nb de routes, présence d'assets Vue,
  présence d'un dossier `tests/Unit/Module/X/`, présence d'un dossier
  `tools/<x>/`.
- [ ] Classifier chaque module : **core** (non-extractible, livré avec
  aurora-core) vs **métier** (extractible en package séparé) vs **à
  débattre**. Critères de classement core :
  - Le module est consommé par tous les autres modules (User, Auth)
  - Le module fournit une infrastructure transversale (Configuration,
    Administration, Dashboard, Ged comme stockage de fichiers)
  - Sans lui, plus rien ne fonctionne (Auth)
- [ ] **Livrable** : tableau modules × statut + justification.

### 1.2 Graphe de dépendances inter-modules

- [ ] Pour chaque module `X`, exécuter :
  ```bash
  grep -rn "use Aurora\\\\Module\\\\" src/Module/<X>/ | grep -v "use Aurora\\\\Module\\\\<X>\\\\"
  ```
  pour repérer les imports d'autres modules.
- [ ] Repérer les **Entity FK cross-module** : grep
  `targetEntity: ` dans `src/Module/*/Entity/` et identifier les cas où
  l'entité référencée vit dans un autre module.
- [ ] Repérer les **subscribers cross-module** : un module qui écoute des
  events d'un autre module.
- [ ] Repérer les **services cross-module** : un service de module A
  injecté dans un service de module B.
- [ ] Identifier les Vue components cross-module (`import '@<other>/...'`).
- [ ] **Livrable** : graphe de dépendances (Mermaid). Tout cycle ou
  dépendance forte = blocker à résoudre avant split.

### 1.3 Couplage à `AuroraBundle.php`

- [ ] Lister tout ce qui est centralisé dans `src/AuroraBundle.php` :
  `$resolve_target_entities`, configs DI, paths globbés.
- [ ] Pour chaque entrée, identifier le module d'origine.
- [ ] **Livrable** : tableau « entrée → module » + recommandation de
  distribution (« reste en core » / « va dans le bundle module X »).

---

## Phase 2 — Mécanismes d'auto-discovery

L'enjeu : tout ce qui est aujourd'hui auto-découvert par glob depuis
`src/Module/*` doit pouvoir être redéclaré par chaque sous-package, sans
casser les modules qui restent en core.

### 2.1 Doctrine mappings

- [ ] Repérer comment les entités sont mappées aujourd'hui (annotations
  PHP 8 + glob `src/Module/*/Entity` ?).
- [ ] Lire le code qui enregistre les mappings (probablement dans
  `AuroraBundle::loadExtension()` ou équivalent).
- [ ] Concevoir le pattern pour qu'un sous-bundle (`AuroraBillingBundle`)
  enregistre ses propres mappings depuis son propre `src/Entity/`.

### 2.2 Twig namespaces

- [ ] Localiser le chargement des templates par module
  (`src/Module/X/Resources/views/` ? `src/Module/X/templates/` ?).
- [ ] Vérifier la config Twig actuelle (`config/packages/twig.yaml` ou
  registration programmatique dans le bundle).
- [ ] Concevoir le pattern de namespace Twig par sous-bundle.

### 2.3 Traductions

- [ ] Localiser tous les `translations/` (root + `src/Module/X/translations/` ?).
- [ ] Lire `DumpJsTranslationsCommand` : comment scanne-t-il les paths ?
  Hardcodé sur `src/Module/*/translations` ? Configurable via
  `$extraSourceDirs` (cf. `extracting_a_module.md` Phase 4) ?
- [ ] Concevoir le pattern : chaque sous-bundle déclare ses
  `translations/` et les enregistre côté Symfony + côté JS dump.

### 2.4 Routes

- [ ] Repérer le chargement des routes : `config/routes/` ? Attributs PHP
  `#[Route]` ? Auto-discovery via `AnnotationDirectoryLoader` ?
- [ ] Pour chaque module, lister ses routes.
- [ ] Concevoir le pattern de chargement depuis un sous-bundle (le sous-bundle
  expose un `routes.php` ou équivalent que le client importe).

### 2.5 Services DI

- [ ] Inventorier `config/services.yaml` + `config/services/` + ce que
  chaque module charge.
- [ ] Identifier les services partagés (Core/) vs spécifiques module.
- [ ] Vérifier les `#[AsAlias]` cross-module : si Billing alias un
  service Core, l'alias reste OK (le client a Core en dépendance dure).
  Mais si Ecommerce alias un service Billing, Ecommerce doit déclarer
  Billing en dépendance Composer.

### 2.6 Permissions, NavSections, Module Toggles

- [ ] Lister les `ModuleParameterEnum` cases par module
  (`src/Module/Configuration/Setting/Enum/ModuleParameterEnum.php`).
- [ ] Vérifier comment les permissions sont déclarées par module
  (probablement via un `PermissionProvider` par module).
- [ ] Vérifier comment les NavSections sont registrées
  (`getNavSections()` / `getCatalogNavSections()` côté module class).
- [ ] **Question architecturale** : si Billing est un package séparé,
  doit-il continuer à ajouter ses cases au `ModuleParameterEnum` de
  Configuration ? Probablement via un mécanisme de contribution
  (collecteur DI tagué) plutôt que enum hardcodé. À designer.
- [ ] Vérifier que `ModuleToggleProviderInterface` est compatible avec
  bundles séparés (devrait l'être par construction).

---

## Phase 3 — Assets Vue/Vite (le gros morceau)

C'est le point le plus risqué. Aurora a la particularité que le build Vite
vit côté client (`aurora-client` consomme les sources et build avec son
propre `vite.config.js`).

### 3.1 Inventaire assets actuels

- [ ] Lister tous les `src/Module/X/assets/backend/` (et `frontend/` si
  applicable).
- [ ] Identifier les entry points Vite (lire `vite.config.js`).
- [ ] Repérer les **imports cross-module** : grep
  `from '@<other>/` dans `src/Module/X/assets/` pour chaque pair de
  modules.
- [ ] Vérifier les conventions d'imports actuelles (cf. `aliases.js` à la
  racine).

### 3.2 Build pipeline actuel

- [ ] Lire `vite.config.js` à la racine d'aurora-core.
- [ ] Lire `package.json` à la racine d'aurora-core.
- [ ] Lire le `vite.config.js` côté aurora-client (dans
  `aurora-client/vite.config.js` ou équivalent).
- [ ] Comprendre comment aurora-client résout les sources d'aurora-core
  aujourd'hui : symlink `vendor/axelraboit/aurora/src/Module/*/assets/` ?
  Plugin Vite custom ? Glob explicite ?

### 3.3 Aliases Vue (`aliases.js`)

- [ ] Lire `aliases.js` à la racine.
- [ ] Identifier les aliases `@<module>/...` pointant sur des modules.
- [ ] Comprendre comment il est consommé (par `vite.config.js` côté core ?
  côté client ?).
- [ ] **Question** : si chaque module-package livre ses sources dans
  `vendor/axelraboit/aurora-billing/assets/`, comment client le
  découvre-t-il automatiquement ?

### 3.4 Stratégie de distribution — évaluer 3 options

- [ ] **Option A — Pré-buildé** : chaque sous-package publie son JS
  compilé. Côté client, import direct depuis `vendor/`.
  - Pro : pas de problème de résolution
  - Con : fige les versions de Vue, perd HMR, le client ne peut plus
    customiser via aliases

- [ ] **Option B — Plugin Vite custom + sources** : chaque sous-package
  publie ses sources `.vue`/`.js`. Côté client, un plugin Vite scanne
  `vendor/axelraboit/aurora-*/` et génère les aliases dynamiquement.
  - Pro : HMR préservé, customization client OK, élégant
  - Con : complexité, debug Vite plugin

- [ ] **Option C — Composer post-install symlink** : un hook
  `composer install` symlink ou copie `vendor/axelraboit/aurora-*/assets/`
  vers un dossier connu (`assets/aurora-vendor/`).
  - Pro : simple, Vite voit tout comme des fichiers locaux
  - Con : sale (duplication ou symlinks fragiles, prod vs dev)

- [ ] **Livrable** : POC chacune des 3 options sur un seul module (Billing
  recommandé) et choisir.

---

## Phase 4 — Tests

### 4.1 Distribution actuelle

- [ ] Inventorier `tests/Unit/Module/X/` vs `tests/Unit/Core/` vs
  `tests/Integration/`.
- [ ] Repérer les fixtures partagées (`tests/Fixture/`, base
  `WebTestCase`, base `KernelTestCase`).
- [ ] Repérer les bootstraps de test (`phpunit.xml`, `tests/bootstrap.php`).

### 4.2 Stratégie monorepo

- [ ] Pattern recommandé : chaque sous-package porte ses tests dans son
  propre dossier, mais le monorepo garde un `phpunit.xml` racine qui
  lance toute la suite en une commande.
- [ ] **Fixtures partagées** : rester en core (`aurora-core` Test utilities)
  + chaque sous-package require `aurora-core --dev`.
- [ ] Vérifier que `splitsh/lite` peut splitter `tests/Module/X/` en même
  temps que `src/Module/X/` (chemins multiples par sous-package).

---

## Phase 5 — Migrations Doctrine

### 5.1 État actuel

- [ ] Lister `migrations/` — sont-elles toutes dans un seul dossier ?
- [ ] Repérer la convention de nommage (`Version<YYYYMMDD>_<module>_*.php` ?).
- [ ] Identifier si les migrations sont taggées par module ou globales.

### 5.2 Stratégie

- [ ] Option A : toutes les migrations restent dans `aurora-core` (le
  client a forcément aurora-core en dépendance, donc OK).
- [ ] Option B : chaque sous-package porte ses migrations dans
  `vendor/axelraboit/aurora-billing/migrations/`. Doctrine Migrations
  supporte plusieurs sources via `migrations_paths`. Côté client,
  configurer une entrée par sous-package installé.
- [ ] **Recommandation provisoire** : Option B (cohérent avec la
  distribution). À valider via POC.

---

## Phase 6 — Mémoires Claude + Docs

### 6.1 `aurora-shared` et `aurora-core/`

- [ ] Confirmer que `aurora-shared` reste dans `aurora-core` (dépendance
  dure côté client). Vérifier le chemin de lecture côté client :
  `vendor/axelraboit/aurora-core/.claude/memory/aurora-shared/`.
- [ ] Confirmer que `aurora-core/` (mémoires) reste dans `aurora-core`.

### 6.2 Mémoires spécifiques module

- [ ] Inventorier `.claude/memory/aurora-core/**/` pour repérer les
  fichiers `pitfall_<x>_*.md` ou `convention_<x>_*.md` qui sont
  spécifiques à un module métier (Billing, CRM, etc.).
- [ ] **Décision à prendre** : ces mémoires partent-elles dans le
  sous-package correspondant (`vendor/axelraboit/aurora-billing/.claude/memory/`),
  ou restent-elles centralisées ? La cohérence avec
  `extracting_a_module.md` (qui déplace ces mémoires) suggère de les
  déplacer.

### 6.3 Docs par module

- [ ] Inventorier `docs/aurora-core/dev/*<module>*` ou
  `docs/aurora-core/todo/<module>/`.
- [ ] **Décision** : déplacer dans le sous-package ou garder en
  aurora-core ? Recommandation provisoire : garder en aurora-core pour
  cohérence (un seul endroit pour tout le savoir).

---

## Phase 7 — Outillage monorepo

### 7.1 Choix d'outil — POC

- [ ] POC `splitsh/lite` : splitter `src/Module/Billing/` (+ tests +
  assets + traductions associées) vers un repo séparé, vérifier que
  l'historique git est cohérent.
- [ ] Évaluer `symplify/monorepo-builder` en parallèle.
- [ ] **Livrable** : recommandation argumentée + setup minimal documenté.

### 7.2 Composer.json structure

- [ ] Concevoir le `composer.json` racine du monorepo (utile pour le dev
  local : require all submodules in dev).
- [ ] Concevoir le template `composer.json` pour chaque sous-package :
  - `name`, `description`, `keywords`
  - `require`: PHP + Symfony + `axelraboit/aurora-core`
  - `autoload`: psr-4 namespace
  - `extra.symfony.bundle-class`: pointer le `AuroraBillingBundle.php`
- [ ] Définir la convention de **versioning** :
  - Synchronisé (tous packages au même tag, comme Symfony) — simple,
    cohérent, recommandé
  - Indépendant par package — flexible mais lourd
- [ ] Gérer les `require` entre sous-packages (ex: si `aurora-ecommerce`
  dépend de `aurora-billing`).

### 7.3 CI/CD

- [ ] Lister les jobs CI actuels (`.github/workflows/`).
- [ ] Adapter pour le monorepo :
  - **Test** : sur le monorepo entier, tous tests en une CI run
  - **Split** : à chaque tag (ou push develop), splitsh push vers les
    repos enfants
  - **Publish** : Packagist détecte les tags des repos enfants
- [ ] Vérifier l'auth GitHub pour le split (PAT avec push sur N repos).

---

## Phase 8 — Skills et tooling Claude

### 8.1 Impact sur les skills existants

Vérifier chaque skill listé ci-dessous et identifier les modifications
nécessaires si le code source est splitté :

- [ ] `add-module` : génère aujourd'hui un dossier dans `src/Module/`.
  Devra-t-il générer un nouveau sous-package complet à la place ?
- [ ] `add-entity` : patche `AuroraBundle::$resolve_target_entities`.
  Devra patcher le `AuroraXBundle` du sous-module concerné.
- [ ] `add-submodule` : ajoute une sub-feature dans un module existant.
  Restera local au sous-package.
- [ ] `register-module-toggle` : édite `ModuleParameterEnum` et un
  Context. Si l'enum reste en core, OK. Sinon refonte.
- [ ] `audit-module-toggles` : scanne `src/Module/*`. Devra scanner
  `vendor/axelraboit/aurora-*/` côté client.
- [ ] `check-extensibility` : audit Sylius pattern sur une entité. Sera
  appelé sur des entités vivant dans n'importe quel sous-package.
- [ ] `extend-aurora-entity` : côté client, étend une entité aurora.
  Devra pouvoir résoudre dans quel sous-package vit l'entité parente.
- [ ] `add-crud-list-ui` : génère du Vue. Sera appelé dans le contexte
  d'un sous-package.

- [ ] **Livrable** : matrice skill × impact (aucun / mineur / refonte) +
  liste des templates `.claude/skills/*/templates/` à adapter.

---

## Phase 9 — POC ciblé

Avant de tout migrer, valider la mécanique sur un seul module.

### 9.1 Module pilote

- [ ] **Recommandation** : `Billing` (entité bien isolée, peu de
  dépendances cross-module attendues — à confirmer via le graphe Phase 1.2).
- [ ] Alternatives : `Photo` (autre candidat isolé), `Editorial` (à
  éviter si tightly coupled avec Ged).

### 9.2 Setup POC

- [ ] Créer une branche `feat/monorepo-split-poc`.
- [ ] Configurer splitsh/lite pour produire `axelraboit/aurora-billing` à
  partir de `src/Module/Billing/` + dossiers associés.
- [ ] Créer un `AuroraBillingBundle.php` qui enregistre Doctrine
  mappings, Twig namespace, routes, traductions, ModuleToggle.
- [ ] Splitter une première fois, créer le repo `aurora-billing` sur
  GitHub.
- [ ] Côté `aurora-client` : installer `composer require
  axelraboit/aurora-core ^X axelraboit/aurora-billing ^X`.
- [ ] Vérifier que tout fonctionne (cf. critères 9.3).

### 9.3 Critères de succès du POC

- [ ] Le client peut `composer require axelraboit/aurora-core ^X` sans
  Billing → l'app boot, mais aucune route Billing, aucune entité
  Billing en DB.
- [ ] Le client peut ajouter `composer require axelraboit/aurora-billing`
  → routes, entités, traductions, Vue components, permissions Billing
  toutes actives.
- [ ] `php bin/phpunit` (côté core) passe.
- [ ] `php bin/phpunit` (côté package billing) passe.
- [ ] Le build Vite côté client compile correctement les assets Billing
  (selon l'option retenue Phase 3.4).
- [ ] `php bin/console doctrine:schema:validate` OK avec et sans Billing
  installé.
- [ ] `php bin/console debug:router` montre les routes Billing seulement
  quand le package est installé.
- [ ] Les fixtures fonctionnent.
- [ ] L'extensibilité Sylius-style continue de marcher (étendre
  `BillingInvoice` depuis le client, override le manager, etc.).

---

## Livrable final de l'audit

Un document de synthèse `docs/aurora-core/dev/monorepo_split_plan.md`
contenant :

- [ ] **Cartographie complète** (Phase 1) : modules + dépendances +
  couplages, avec graphe Mermaid.
- [ ] **Recommandations techniques par phase** : pour chaque mécanisme
  (Doctrine, Twig, traductions, routes, services, assets, tests,
  migrations), la solution retenue.
- [ ] **Effort estimé** (jours-homme) par phase + total.
- [ ] **Roadmap de migration** : ordre des modules à extraire, pourquoi
  cet ordre (en commençant par le plus isolé).
- [ ] **Risques identifiés + plans de mitigation**.
- [ ] **Décision finale** : Go / No-Go basé sur le ratio
  effort × gain × risque.

---

## Notes pour l'auditeur (Claude Code)

- Commencer par Phase 1 (cartographie). Sans ça, toutes les phases
  suivantes sont aveugles. Le graphe de dépendances inter-modules
  conditionne tout : si Billing dépend lourdement de CRM, le split
  individuel n'a pas de sens, il faudra grouper.
- Phase 3 (assets Vue) est le point d'incertitude maximale. Si aucune
  des 3 options ne convainc, le projet est compromis — il faudra peut-être
  rester sur 1 package Composer.
- Phase 7.1 (POC outillage) peut être faite en parallèle de Phase 2 et 3
  par un autre agent.
- Ne pas hésiter à proposer de **modifier `extracting_a_module.md`** si
  l'audit révèle qu'une partie de sa logique devient générique au split
  monorepo.
- Tous les livrables intermédiaires (cartographie, graphe, matrices,
  comparatifs) doivent être posés dans `docs/aurora-core/dev/audit/`
  pour traçabilité, puis synthétisés dans le livrable final.
