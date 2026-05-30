# Audit J1.3 — Couplage à `AuroraBundle.php`

> Livrable de la **Phase 1.3** de `audit_monorepo_split.md`, jalon **J1**.
> Source : `src/AuroraBundle.php` (470 lignes) au **2026-05-30**.

`AuroraBundle` est le **point de centralisation unique** de tout le
wiring. Pour un split, chaque sous-bundle (`AuroraBillingBundle`, …) doit
réémettre sa part. Inventaire de tout ce que le bundle prepend, avec
l'origine et la reco de distribution.

## Tableau « entrée → origine → distribution »

| Entrée dans `AuroraBundle` | Mécanisme | Granularité actuelle | Distribution cible |
|---|---|---|---|
| **`doctrine.dbal.types`** (`EncryptedText/StringType`) | liste statique | Core | **Reste core** (types transverses) |
| **`doctrine.orm` flags** (naming_strategy, identity prefs, `auto_mapping:false`, validate_xml) | config statique | Core | **Reste core** (chaque sous-bundle hérite, ne redéclare pas) |
| **`resolve_target_entities`** (95 paires Interface→concrete) | **liste manuelle** | **Toutes modules** | ⚠️ **Splitter par module** : chaque sous-bundle déclare SES paires. Seul couplage 100 % manuel. |
| **`doctrine.orm.mappings`** | `glob(src/Module/*)` + `src/Core` | Auto par glob | Chaque sous-bundle mappe son propre `src/` (1 mapping). Core mappe `Core/`. |
| **`twig.paths`** (overrides client + bundle defaults) | `glob(src/Module/*/templates)` + chemins client | Auto par glob | Chaque sous-bundle enregistre son `templates/` + sa logique d'override client. **Logique d'override à factoriser** (helper partagé core). |
| **`doctrine_migrations.migrations_paths`** | chemin unique `/migrations` | **Monolithe** | ⚠️ **Concern Phase 5** : 17 migrations dans 1 dossier non partitionné. Soit chaque sous-bundle a son `migrations/`, soit on garde un dossier central côté client. |
| **`framework.translator.paths`** | `glob(src/Module/*/translations` + `*/*/translations)` + `src/Core/*/translations` | Auto par glob | Chaque sous-bundle déclare ses `translations/`. |
| **`framework.default_locale` / `enabled_locales`** | `LocaleEnum` (Core) | Core | **Reste core**. |
| **`getPath()` override** | retourne `__DIR__` (anti-nesting assets) | Core | Chaque sous-bundle aura le même besoin → **pattern à dupliquer** dans chaque `Aurora*Bundle`. |
| **`loadExtension` → import `services.yaml`** | 1 import racine | Tous services | ⚠️ Chaque sous-bundle importe SON `services.yaml`. Découper `config/services.yaml` actuel par module. |

## Couplages structurels révélés

1. **Auto-discovery par glob = atout majeur.** Mappings Doctrine, Twig,
   traductions sont déjà découverts par `glob(src/Module/*)`. Le pattern
   « un sous-bundle déclare son propre `src/` » est mécaniquement le même,
   juste relocalisé. **Peu de réécriture**, surtout du déplacement.

2. **`resolve_target_entities` est le seul vrai point dur manuel.** 95
   paires listées à la main. Après split, chaque `Aurora<Module>Bundle`
   porte ses paires (cohérent avec la checklist CLAUDE.md §8 : « seule
   ligne manuelle nécessaire »). Côté client, plus besoin de toucher un
   fichier central — on installe le package, le bundle s'enregistre.

3. **`config/services.yaml` (racine) est partagé.** À auditer en Phase 2.5
   pour le découper par module sans casser les `#[AsAlias]` cross-module
   (cf. `dependency_graph.md` : un alias Erp→Ecommerce existe → Erp devra
   *require* Ecommerce, ou on découple l'enum).

4. **Logique d'override Twig client** (priorité client > bundle,
   co-located + legacy) est dans `AuroraBundle` (≈40 lignes). Elle doit
   être **factorisée dans un trait/helper de `aurora-core`** réutilisé par
   chaque sous-bundle, sinon duplication massive.

5. **Migrations non partitionnées** = le point le plus inconfortable. 17
   migrations mélangent tous les modules. À trancher en Phase 5 :
   - (a) repartir d'un schéma propre par package (perte d'historique) ;
   - (b) garder les migrations côté **client** (le client orchestre le
     schéma de tous ses packages installés) — **probablement le plus sain**.

## Reco de séquençage

- L'extraction d'un module = principalement **déplacer du code déjà
  glob-isolé** + **créer un `Aurora<Module>Bundle`** qui réémet : 1 mapping
  Doctrine, ses `resolve_target_entities`, son `templates/`, ses
  `translations/`, son `services.yaml`, l'override `getPath()`.
- **Pré-requis core** (à faire AVANT le 1er split, en J3/POC) :
  - extraire un **`AbstractAuroraModuleBundle`** dans `aurora-core` portant
    la logique commune (glob de SON `src/`, override Twig client,
    `getPath()`), pour que chaque sous-bundle se réduise à ~30 lignes ;
  - rendre **`ModuleParameterEnum` extensible** (cf. `module_inventory.md`)
    — sinon chaque sous-bundle ne peut pas déclarer ses toggles.
