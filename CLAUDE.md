# Aurora-core — Guide pour Claude

Ce fichier est chargé automatiquement par Claude Code à chaque session dans ce
dépôt. Il résume les conventions et points d'entrée nécessaires pour bien
travailler sur `aurora-core` et son écosystème (`aurora-client`).

> **📚 Base de mémoire structurée** : voir [`.claude/memory/MEMORY.md`](.claude/memory/MEMORY.md)
> pour l'index racine. Les mémoires sont organisées en deux sous-dossiers :
> - [`.claude/memory/aurora-core/`](.claude/memory/aurora-core/MEMORY.md) —
>   conventions, décisions, pièges et préférences propres au bundle core.
>   Toute nouvelle mémoire core va ici (un fichier `.md` + ligne dans l'index).
> - [`.claude/memory/aurora-client/`](.claude/memory/aurora-client/MEMORY.md) —
>   patterns d'extension côté consommateur, distribués via composer
>   (lus depuis `vendor/axelraboit/aurora/.claude/memory/aurora-client/`).

---

## 1. Stack et architecture

- **PHP 8.4 + Symfony 7** côté serveur, **Vue 3 + Vite** côté client
- Bundle distribué : `axelraboit/aurora` (composer), monté dans une app cliente
  via `aurora-client/` (séparé). Les clients consomment aurora-core comme un
  bundle Symfony et étendent des points d'extension typés (Sylius-style).
- Architecture en couches `src/Core/` (infrastructure partagée) +
  `src/Module/{Billing,Crm,Ecommerce,Editorial,Erp,Photo,Project,Ged}/`
  (modules métier autonomes).

**Lecture rapide** : [`docs/aurora-core/dev/app_architecture.md`](docs/aurora-core/dev/app_architecture.md)
pour la cartographie complète (templates, assets, namespaces Twig, etc.).

---

## 2. Convention d'extensibilité (centrale, à respecter scrupuleusement)

Toute entité de aurora-core qui a une page backend CRUD suit le pattern Sylius
en 5 couches. **Doc canonique** :
[`docs/aurora-core/dev/entity_extensibility_convention.md`](docs/aurora-core/dev/entity_extensibility_convention.md).

**Résumé des règles dures** :

1. **Couche 1 — Entity** : `<Name>Interface` + `Abstract<Name>` (MappedSuperclass)
   + concrete `<Name>` non-`final`. Sequence nommée `seq_core_<entity>_id` (le
   préfixe `seq_core_` est obligatoire pour éviter les collisions avec des
   entités client homonymes). Référencé dans `AuroraBundle::$resolve_target_entities`.
2. **Couche 2 — DTO** : `<Name>InputInterface` + `<Name>InputFactoryInterface`
   + `<Name>InputFactory` (avec `#[AsAlias(<Name>InputFactoryInterface::class)]`)
   + `<Name>Input` non-`final` avec `public readonly` sur chaque propriété
   (PAS `readonly class` global — ça empêcherait un client d'ajouter une
   propriété mutable en étendant). Pas de `static fromArray()` dans le DTO,
   c'est la factory qui le fait.
3. **Couche 3 — Manager** : `<Name>ManagerInterface` dans `Manager/` (jamais
   `Contract/` — l'ancien dossier est interdit pour les Managers
   instrumentés). `<Name>Manager` non-`final` + props `protected readonly` +
   `#[AsAlias(<Name>ManagerInterface::class)]`. Trois familles de hooks :
   - **Instanciation** : `protected create<X>(): <X>Interface` pour
     **chaque classe** que le Manager instancie (sans exception).
   - **Hydratation** : `protected applyInput(<Name>Interface, <Name>InputInterface)`,
     sauf variante User-style (3 critères : ≥6 méthodes spécialisées, pas de
     create+update simple, validation/sécurité distincte par opération).
   - **Audit** : `protected auditCreated/Updated/Deleted` + `auditPayload`.
     Les domain events (validate, paid, stage_changed, …) restent inline mais
     splat-mergent `auditPayload()` pour rester extensibles.
4. **Couche 4 — Serializer** : `<Name>SerializerInterface` + `<Name>Serializer`
   non-`final` + `#[AsAlias]`.
5. **Couche 5 — Vue** : `<Plural>App.vue` avec props `extraFields` + slots
   `extra-headers`/`extra-cells`/`extra-form-fields` ; composable
   `useXxxForm.js` unifié create+edit avec option `extraFields`.

**Variantes structurelles documentées** (3 cas) :
- Manager à hooks multiples sans `applyInput` (User, Menu pré-DTO, Billing,
  Order)
- Composables Vue séparés `useXxxCreate` + `useXxxEdit` (User invite/edit, Theme)
- Editor full-page au lieu de modal (Post)

**Repository** : `<Name>Repository` étend
`Aurora\Core\Repository\ResolveTargetEntityRepository` (jamais
`ServiceEntityRepository` directement). Pas d'interface aurora-core pour les
finder methods custom — limite documentée, le client étend le repo et
déclare son propre `repositoryClass` sur l'entité concrète.

---

## 3. Côté client (aurora-client)

Pour étendre une entité depuis l'app client :
- **Cheatsheet** : [`docs/aurora-core/dev/client_quickstart.md`](docs/aurora-core/dev/client_quickstart.md)
- **Guide pas-à-pas** (exemple Agency complet) :
  [`docs/aurora-core/dev/extending_agency_pilot.md`](docs/aurora-core/dev/extending_agency_pilot.md)
- **Vue d'ensemble** : [`docs/aurora-core/dev/extending_aurora.md`](docs/aurora-core/dev/extending_aurora.md)

Patterns clés pour étendre :
- Substituer une entité : étendre `Abstract<Name>`, déclarer
  `#[ORM\Entity(repositoryClass: …)]`, mettre à jour
  `App\AuroraBundle::$resolve_target_entities` côté client.
- Substituer un DTO : étendre `<Name>Input`, étendre `<Name>InputFactory`,
  décorer la factory via `#[AsAlias(<Name>InputFactoryInterface::class)]`.
- Substituer un Manager : étendre, override les hooks `protected`
  (`create<X>()`, `applyInput()`, `auditPayload()`), décorer via
  `#[AsAlias(<Name>ManagerInterface::class)]`.
- Substituer un Serializer : pareil.
- Étendre la Vue : passer la prop `extraFields` + utiliser les slots scoped
  (`extra-headers`, `extra-cells`, `extra-form-fields`).

---

## 4. Conventions de naming (à appliquer)

- **Variables** : noms complets (jamais 1-2 lettres). Ex : `$company`, pas
  `$c` ; `$translation`, pas `$tr`.
- **Repos: éviter le N+1** : `findBy(['id' => $ids])` plutôt que `find()`
  dans une boucle pour hydrater plusieurs entités.
- **Manager vs Service** :
  - `Manager/` = classes qui persistent / flushent / orchestrent un cycle
    de vie d'entité.
  - `Service/` = logique stateless pure (helpers, calculs, validateurs).
- **DTO** : dossier `Dto/` (jamais `DTO/` majuscules — l'acronyme reste
  "DTO" en prose mais le namespace est `Dto`).
- **Tests** : helper d'instanciation dans le test si l'API DTO change
  beaucoup, plutôt que recopier `new XxxInput(...)` partout.

---

## 5. Commandes utiles

```bash
# Tests (492+ tests, doivent rester verts)
php bin/phpunit

# Build assets Vue
npm run build

# Lint Symfony
php bin/console lint:twig templates/
php bin/console lint:yaml config/

# Cache (souvent nécessaire après refacto DI)
php bin/console cache:clear --env=test

# Schema/migrations Doctrine
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:diff
```

**Avant chaque commit** : tests verts + build OK. Pas d'exception.

---

## 6. Conventions Git / commits

- **Pas de `Co-Authored-By` Claude** dans les messages de commit (préférence
  utilisateur explicite).
- **Pas de `--no-verify`** sur les hooks pre-commit. Si un hook échoue,
  fixer la cause.
- **Préfixes de message standardisés** : `feat:`, `refactor:`, `docs:`,
  `fix:`, `test:` (suivre l'historique récent : `git log --oneline -20`).
- **Commits atomiques par entité** lors du rollout d'extensibilité (cf
  l'historique récent : 24 commits, un par entité).

---

## 7. État du rollout d'extensibilité

✅ **24/24 entités instrumentées** (rollout terminé).
- Commits : `git log --oneline --grep="instrument"` pour la liste

Plus aucune entité ne devrait avoir un Manager `final readonly` ou un dossier
`Contract/` (sauf pour des interfaces non-Manager légitimes : provider
patterns, location registries, etc.).

---

## 8. Checklist pour ajouter une nouvelle entité Aurora

1. Créer `Interface + Abstract + concrete` dans `Entity/` avec sequence
   `seq_core_<entity>_id`.
2. Ajouter au `resolve_target_entities` de `AuroraBundle.php` — **seule
   ligne manuelle nécessaire**. Tout le reste est auto-découvert par glob :
   Doctrine mappings, Twig namespaces, Symfony translator paths,
   DumpJsTranslationsCommand.
3. Repository qui étend `ResolveTargetEntityRepository`.
4. **Si backend CRUD** : 4 fichiers DTO (Input, InputInterface,
   InputFactoryInterface, InputFactory) + Manager (Interface + class non-final
   + AsAlias + hooks) + Serializer (Interface + class non-final + AsAlias) +
   Controller (type-hint les interfaces) + Vue (extraFields + slots).
5. Ajouter à la table 2.1 de `entity_extensibility_convention.md` si la
   liste change.
6. Tests + build verts, commit atomique.

> **Créer un nouveau module** (`src/Module/<Module>/`) : le dossier seul
> suffit pour que Doctrine, Twig et les traductions le découvrent
> automatiquement. Seul `resolve_target_entities` est à renseigner
> manuellement pour chaque entité du module.

---

## 9. Pièges connus

- **Doctrine resolve_target_entities** ne s'applique qu'aux relations Doctrine,
  pas aux `new <Name>()` directs. C'est pour ça que le hook `create<X>()`
  existe : il permet au client de retourner sa classe substituée.
- **`readonly class` PHP 8.2+** force tout enfant à être également `readonly`
  → plus difficile à étendre. Préférer `class { public readonly … }` par
  propriété.
- **Sub-DTO** (ex: `PostTranslationInput` dans `PostInput`) : restent
  `final readonly`, **pas instrumentés**. Seul le DTO racine consommé par le
  controller a une factory + interface.
- **`#[AsAlias]` sur l'interface** : permet la substitution. Mais la
  décoration via `#[AsDecorator]` ne marche que si les consommateurs
  type-hint l'**interface**, pas la classe concrète. Toujours type-hint
  l'interface dans les controllers/services tiers.
