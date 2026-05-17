---
name: decision-core-submodule-nesting
description: Décision (mai 2026) — les sous-modules Core (User, Agency, Service, Auth, Setting, Theme, Audit, Dashboard, Profile, Search, Media) vivent désormais sous le dossier de leur module parent (Aurora\Core\Platform\User, etc.) pour aligner avec la convention Vault-style déjà en place côté src/Module/.
metadata:
  type: project
---

# Décision : nesting des sous-modules Core sous leur module parent

## Décision (mai 2026)

Les entités Core qui appartiennent logiquement à un `<X>Module` vivent
désormais dans un sous-dossier de ce module, pas au top-level de
`src/Core/`. Conséquences concrètes :

| Module parent | Sous-modules nichés sous lui |
|---|---|
| `PlatformModule` | `Platform/User`, `Platform/Agency`, `Platform/Service`, `Platform/Auth` |
| `ConfigurationModule` | `Configuration/Setting`, `Configuration/Theme` |
| `GeneralModule` | `General/Dashboard`, `General/Profile`, `General/Search` |
| `MediaModule` | `Media/Library` |
| `DevModule` | `Dev/Audit` |

Le **fichier `<X>Module.php` reste à la racine** de `src/Core/` (jamais
sous `src/Core/Platform/PlatformModule.php` — toujours
`src/Core/PlatformModule.php`).

## Why

Avant : 2 conventions co-existaient. Côté `src/Module/` (Vault, Notes,
Editorial…) les sous-modules vivaient dans un sous-dossier
(`src/Module/Vault/Safe/`, `src/Module/Notes/Markdown/`). Côté
`src/Core/`, les sous-modules étaient à plat (`src/Core/User/`,
`src/Core/Agency/` — alors que ces 3 entités appartiennent à
`PlatformModule`). Incohérence pénible :

- **Discoverabilité** : un dev nouveau cherchant "tout ce qui est
  Platform" doit grep, pas `cd`. Idem pour Configuration, Media, etc.
- **Cohésion logique** : module = vue abstraite (sidemenu + permissions
  + toggles) dispersée à travers le tree de fichiers.
- **Skills Claude / scaffold** : `/add-module` et `/add-submodule`
  doivent encoder 2 conventions au lieu d'1 — friction sur l'outillage.

## How to apply

### Pour un nouveau sous-module Core

Toujours créer sous `src/Core/<ParentModule>/<SubModule>/`. Exemple :
ajouter une sub-feature `Webhook` à `ConfigurationModule` →
`src/Core/Configuration/Webhook/Entity/Webhook.php`, namespace
`Aurora\Core\Configuration\Webhook\Entity\Webhook`.

### Pour un nouveau module Core (rare)

`<Name>Module.php` reste flat : `src/Core/<Name>Module.php`, namespace
`Aurora\Core\<Name>Module`. Ses sous-modules vont sous `src/Core/<Name>/`.

### Pour aurora-client qui étend une entité Aurora

Le chemin client miroir le namespace Aurora. Avant : `Aurora\Core\Agency`
→ `src/Module/Core/Agency/` côté client. Après : `Aurora\Core\Platform\Agency`
→ `src/Module/Core/Platform/Agency/`. Cf.
[`convention_module_structure.md`](../../aurora-client/convention_module_structure.md)
(à mettre à jour côté client si pas déjà fait).

### Pour les contextes des modules (PlatformContext, VaultContext, etc.)

**Convention unique** : `<racine-du-module>/<Module>Context.php` (à la
racine du folder du module, à côté des sous-modules). S'applique core
ET business :

- **Core** : `src/Core/Platform/PlatformContext.php`,
  `src/Core/Configuration/ConfigurationContext.php`,
  `src/Core/Media/MediaContext.php`,
  `src/Core/General/GeneralContext.php`.
- **Business** : `src/Module/Vault/VaultContext.php`,
  `src/Module/Editorial/EditorialContext.php`, etc. (12 modules).

L'historique : avant l'alignement, les Core contexts vivaient à
`src/Core/Service/`, puis à `src/Core/Module/Context/` (étape
intermédiaire éphémère), avant d'atterrir à la racine du folder du
module. Les Business contexts vivaient à
`src/Module/<X>/Service/<X>Context.php` (10/12 modules n'avaient QUE le
Context dans Service/ — dossier trompeur). Le pattern actuel reflète
mieux la réalité : le Context **appartient** au module et vit à sa
racine, comme `<Module>FrontendDescriptor.php`.

Le dossier `Service/` reste valide pour des **vrais services métier**
(ex. `Crm/Service/CrmNotificationService.php`,
`PdfForm/Service/PdfManipulator.php`) — pas pour le Context.

## Périmètre exclu

`src/Core/Notification/` reste à plat — n'a pas de NavItem dédié,
c'est de l'infra cross-cutting. Idem pour Encryption, Frontend, Locale,
Mail, Menu, Migration, Module, MountPoint, Repository, Scheduler,
Sequence, Storage, Support, Timestampable, Twig, Validation.

## Commits du refacto

Le refacto a été exécuté en 5 commits atomiques :
- `71b4151c` — Prep (élargir glob translations à depth 2)
- `db277d5d` — Étape 1 : General (Dashboard + Profile + Search)
- `996a7f64` — Étape 2 : Dev (Audit)
- `4c34062d` — Étape 3 : Configuration (Setting + Theme)
- `ff9c9e16` — Étape 4 : Media (Library)
- `a380781e` — Étape 5 : Platform (User + Agency + Auth + Service)

Méthode : `git mv` puis `sed -i` sur les namespaces (RenameNamespaceRector
absent de la version Rector installée). Pattern sed validé sur la plus
petite étape (General) avant d'attaquer les grosses (Platform 413
fichiers).

## Pièges identifiés pendant le refacto

1. **Pattern sed avec trailing `\\`** : matche `use Aurora\Core\X\Y;`
   mais **rate** `namespace Aurora\Core\X\Y;` quand `Y` est suivi de
   `;` (pas de backslash final). N'est un problème que pour les
   namespaces à profondeur > 2 (Service\Entity\). Solution : 2e passe de
   sed avec patterns sans `\\` final ciblés sur les déclarations namespace.
2. **Conflit `Aurora\Core\Media` vs `Aurora\Core\MediaModule`** : le
   pattern `Aurora\\Core\\Media\\` (avec backslash final) discrimine bien
   les deux (MediaModule n'a pas de `\` après "Media").
3. **`src/Core/Service/` dual-purpose au moment du refacto initial** : hébergeait à la fois l'entité Service ET les contextes
   globaux. Le pattern sed doit cibler explicitement les 7 sous-namespaces
   de Service (`Entity`, `Dto`, `Manager`, …) — pas le préfixe
   `Aurora\\Core\\Service\\` qui capturerait aussi les contextes.

Voir aussi [[pattern-core-submodules-split]] (le pattern "1 module = 1
section" qui motive ce refacto) et la migration guide client
`docs/aurora-client/MIGRATION_0.4.md`.
