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

## Limites honnêtes (propres au POC en monorepo)

Deux briques restent **centralisées** parce que le code de Tools est
physiquement présent dans le repo :

- **Services** : `config/services.yaml` fait `Aurora\: resource: '../src/'`
  → autowire encore les classes Tools. Au vrai split, le package Tools
  embarque **son** `services.yaml` (`Aurora\Module\Tools\: resource: …`).
- **Routes** : `config/routes/` glob les contrôleurs de `src/`. Idem, le
  package Tools embarquera **ses** routes.

Donc désactiver *seulement* le bundle dans le monorepo laisse services +
routes Tools chargés (état incohérent volontaire). Au vrai split, le dir
entier est **absent** du package core → services, routes, entités, Twig,
i18n disparaissent **ensemble**. Le POC prouve la partie risquée
(Doctrine/Twig/i18n/RTE par bundle) ; services/routes sont du déplacement
mécanique de config vers le package.

## Reste pour un package Composer complet (J3+)

- `composer.json` du package `axelraboit/aurora-tools` (autoload PSR-4
  `Aurora\Module\Tools\`, require `axelraboit/aurora-core`).
- `services.yaml` + `routes` embarqués dans le package.
- **`ModuleParameterEnum` extensible** : les toggles Tools
  (`ToolsBackend/Vault/PasswordGenerator`) vivent encore dans l'enum central
  de Configuration → à rendre extensible pour que le package déclare les
  siens (blocker connu, cf. `module_inventory.md`).
- Stratégie **migrations** (Phase 5 audit) : les tables Vault sont créées
  par les migrations centrales ; à partitionner ou laisser côté client.
- Outil de split (`splitsh/lite`) pour publier le sous-arbre en repo dédié.

## Verdict

✅ **Mécanisme validé.** `AbstractAuroraModuleBundle` est réutilisable tel
quel pour les 8 autres leaves purs et, après cat. D/E, pour les 5 modules
restants. Le « choisir ce qu'on installe » fonctionne au niveau bundle ;
le reste (composer.json, services/routes embarqués, splitsh) est du
packaging déterministe.
