# POC — Installabilité à la carte : module = bundle auto-enregistré (Tools)

> POC réalisé le **2026-05-30** sur la branche `develop`. Démontre le
> mécanisme « partir d'un aurora-client et choisir ses modules » sur un
> **leaf pur** (Tools), sans toucher au schéma DB. Prérequis du split
> Composer (J3 du workplan).

## Ce qu'on voulait prouver

Qu'un module (la **section entière** : `Module/Tools/` = Vault +
PasswordGenerator) peut **s'auto-enregistrer via son propre bundle**,
indépendamment du glob central d'`AuroraBundle` — de sorte que **l'ajouter
ou le retirer = installer ou désinstaller le module**.

## Ce qui a été fait

1. **`Aurora\Core\Bundle\AbstractAuroraModuleBundle`** (réutilisable) —
   `prependExtension()` enregistre, pour UN module :
   - le mapping Doctrine (`Aurora<Name>` → dir du module) ;
   - ses `resolve_target_entities` (méthode abstraite) ;
   - son namespace Twig (`@<Name>`, + overrides client co-located/legacy) ;
   - ses `translations/` (+ sous-dossiers).
   `getPath()` est scopé au dir du module (anti-nesting assets).
2. **`Aurora\Module\Tools\AuroraToolsBundle`** — concret, déclare juste
   `moduleName(): 'Tools'` + les 3 paires RTE (VaultEntry/Folder/UserConfig).
3. **`AuroraBundle`** — exclut Tools de son glob (`$extractedModules =
   ['Tools']`) et n'a **plus aucune** entrée RTE/use Tools. Cette liste
   **simule l'absence du package** dans le monorepo (au vrai split, le dir
   `src/Module/Tools/` n'existe pas dans le package core).
4. **`config/bundles.php`** — enregistre `AuroraToolsBundle`. C'est la
   ligne « on/off » du module.

## Preuve (toggle du bundle)

| Bundle dans `bundles.php` | Mappings Vault | Namespaces `@Tools` |
|---|---:|---:|
| **activé** | 6 | 1 |
| **désactivé** | **0** | **0** |

`doctrine:mapping:info`, `debug:router` (routes `backend_tools_*`),
`debug:twig` (`@Tools`) et `doctrine:schema:validate` ✅. **Suite complète
verte : 2747 tests** avec Tools piloté par son seul bundle.

## POC packaging end-to-end (2026-05-30) — services + tags + split validés

Suite au finding ci-dessus, le câblage services-per-package a été monté ET
validé **dans le monorepo** (et non reporté au split) :

1. **`src/Module/Tools/config/services.php`** (shippé dans le package) :
   `services()->load('Aurora\Module\Tools\', moduleDir)` + deux `instanceof()`
   file-scoped (`ModuleInterface` → `aurora.module`,
   `ApplicationParameterProviderInterface` → `aurora.application_parameter_provider`)
   — les **seules** interfaces tagués que les services Tools implémentent
   (vérifié par grep sur les 13 interfaces du `_instanceof` central).
2. **`AbstractAuroraModuleBundle::loadExtension()`** importe
   `<moduleDir>/config/services.php` **s'il existe** → no-op pour les modules
   qui n'en ont pas (ils restent sur le glob central).
3. **`config/services.yaml`** : `exclude: ['../src/Module/Tools/']` sur le
   `Aurora\: resource` central — Tools n'est plus autowiré deux fois.

Vérifs : `cache:clear` (test + dev) ✅ **sans conflit de merge**,
`lint:container` ✅, `ToolsModule`/`ToolsModuleParameterProvider` portent bien
leurs tags (`debug:container`), compteurs `aurora.module`=18 /
`aurora.application_parameter_provider`=15 intacts, **2744 tests verts**.

**Routes — pas de `routes.php` nécessaire.** Le loader `routing.controllers`
(celui du `config/routes.yaml` de l'app cliente) découvre les contrôleurs
**via leur enregistrement comme services**, pas par glob de répertoire. Les
routes `backend_tools_*` résolvent **alors même** que Tools est exclu du glob
central (elles viennent du `services.php` du module). Un package back-only
n'embarque donc **que** `composer.json` + `config/services.php`.

**Split mécanique validé (`git subtree split`, splitsh-lite absent).**
`git subtree split --prefix=src/Module/Tools -b split-aurora-tools` produit un
arbre où `composer.json`, `AuroraToolsBundle.php` et `config/services.php` sont
**à la racine** → le mapping PSR-4 `"Aurora\\Module\\Tools\\": ""` (le point le
plus à risque du playbook) est **correct** : `Aurora\Module\Tools\AuroraToolsBundle`
↦ `AuroraToolsBundle.php`. Démontré via commit temporaire + soft-reset (aucun
impact sur l'historique de `develop`).

## ⚠️→✅ Apprentissage (2026-05-30) — tagging per-bundle : ce qui marche vraiment

**Première tentative (échec).** Découpler le tagging en migrant le bloc
`_instanceof` central vers des `#[AutoconfigureTag]` sur les interfaces. Symfony
lève `merge() does not support merging autoconfiguration for the same
class/interface` : `#[AutoconfigureTag]` passe par
`registerForAutoconfiguration()`, un registre **global** que plusieurs
extensions de bundle ne peuvent pas merger.

**Finding correctif (validé end-to-end ci-dessous).** Le conflit est spécifique
à l'autoconfiguration **globale**. Un `instanceof()` déclaré **dans le
`services.php` d'un bundle** est *file-scoped* (il ne s'applique qu'aux services
chargés par ce loader) — il **ne passe pas** par `registerForAutoconfiguration`
et **ne crée donc aucun conflit de merge**, même dans le monorepo avec le
`_instanceof` central encore en place. Conséquence : **le câblage
services/tags per-package est testable dans le monorepo**, package par package,
sans attendre le split réel. Ça **dé-risque** tout le chantier.

## Reste pour un package Composer complet (J3+)

- ✅ `composer.json` du package `axelraboit/aurora-tools` (PSR-4
  `Aurora\Module\Tools\: ""`, require `axelraboit/aurora-core`) — **fait**.
- ✅ `config/services.php` embarqué (+ `instanceof` local) — **fait**. Routes :
  inutile (cf. `routing.controllers`).
- ✅ **`ModuleParameterEnum` extensible** — **fait** : `ToolsModuleParameterEnum`
  + `ToolsModuleParameterProvider` déclarent les toggles Tools hors enum central
  (le provider évite aussi le wipe `aurora:application-parameter`).
- ☐ **Install réelle dans un `aurora-client` neuf** : nécessite un repo Composer
  (path/VCS) servant le subtree split + `aurora-core` lui-même packagé. C'est la
  dernière étape **bloquée par l'infra** (repos GitHub + Packagist/privé), pas
  par le code.
- ☐ Stratégie **migrations** (Phase 5 audit) : tables Vault créées par les
  migrations centrales ; à partitionner ou laisser côté client.
- ☐ Outil de split définitif : `splitsh/lite` **absent localement** ;
  `git subtree split` utilisé comme substitut (valide la mécanique + PSR-4 root).

## Verdict

✅ **Mécanisme validé end-to-end** (sauf l'install réelle, bloquée par
l'infra repos/Packagist). `AbstractAuroraModuleBundle` + `config/services.php`
per-module + `composer.json` PSR-4-root sont réutilisables tels quels pour les
8 autres leaves et, après cat. D/E, pour les 5 modules restants. Le finding
**`instanceof` file-scoped ≠ autoconfiguration globale** dé-risque tout le
chantier : le câblage services/tags se valide **dans le monorepo**, package par
package, avant même de créer les repos enfants.

## Généralisation aux 8 leaves (2026-05-30)

Le mécanisme a été appliqué dans la foulée aux **8 modules extractibles**
(les 9 leaves moins `General` qui reste shell core) : un
`Aurora<Name>Bundle` chacun (15-50 lignes : `moduleName()` + `resolve
TargetEntities()`), `AuroraBundle` les exclut tous
(`$extractedModules = [Assistant, Crm, Editorial, Hr, Notes,
PersonalFinance, Planning, Tools]`) et n'a **plus aucune** référence à
eux (RTE + `use` retirés). `config/bundles.php` enregistre les 8 — une
ligne = un module on/off.

Vérifs : `doctrine:schema:validate` ✅, 189 entités mappées, namespaces
Twig `@<Module>` résolus par leurs bundles, 245 routes module présentes,
**suite complète verte (2747 tests)**. `AuroraBundle` ne pilote plus que
Core + les 5 modules encore couplés (Billing, Ecommerce, Erp, Photo,
Project) — qui passeront pareil après cat. D/E.
