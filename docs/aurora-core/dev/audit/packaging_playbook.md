# Playbook de packaging Composer (J4/J5)

> Plan **actionnable** pour exécuter le vrai split : transformer le monorepo
> (1 package `axelraboit/aurora`) en N packages publiables. Suppose le
> découplage in-monorepo **terminé** (graphe en étoile, 13 bundles auto-
> enregistrés, toggles distribués). Voir `package_layout.md` (cible),
> `decoupling_strategy.md` (découplage) et `poc_tools_bundle.md` (bundle POC +
> finding services/routes).

## 0. Pourquoi ça se fait au split réel, pas dans le monorepo

Un package consommé seul charge **uniquement** son `services.yaml` (+ celui de
`aurora-core`). Le monorepo, lui, charge **les 13 bundles + leurs configs
simultanément** → c'est ce qui a fait échouer la migration `_instanceof` →
`#[AutoconfigureTag]` (`merge() does not support merging autoconfiguration`).
**Conclusion** : `services.yaml`/routes/`_instanceof` par package se câblent et
se testent **package par package, en isolation**, jamais en simulant les 13
dans une seule app. Le monorepo reste sur `Aurora\: resource '../src/'` +
`_instanceof` central tant que tout le code est présent.

## 1. Cible (rappel)

13 packages en étoile (tous → `aurora-core`) :

| Package | Contenu | Subtree splitsh |
|---|---|---|
| `axelraboit/aurora-core` | `Core/` + Platform + Configuration + Dev + Ged + General | (le reste, hors `src/Module/<extraits>`) |
| `axelraboit/aurora-commerce` | Ecommerce + Erp | `src/Module/Ecommerce` + `src/Module/Erp` |
| `axelraboit/aurora-crm` | Crm | `src/Module/Crm` |
| `axelraboit/aurora-billing` | Billing | `src/Module/Billing` |
| `axelraboit/aurora-editorial` | Editorial | `src/Module/Editorial` |
| `axelraboit/aurora-photo` | Photo | `src/Module/Photo` |
| `axelraboit/aurora-project` | Project | `src/Module/Project` |
| `axelraboit/aurora-hr` | Hr | `src/Module/Hr` |
| `axelraboit/aurora-notes` | Notes | `src/Module/Notes` |
| `axelraboit/aurora-personal-finance` | PersonalFinance | `src/Module/PersonalFinance` |
| `axelraboit/aurora-planning` | Planning | `src/Module/Planning` |
| `axelraboit/aurora-tools` | Tools | `src/Module/Tools` |
| `axelraboit/aurora-assistant` | Assistant | `src/Module/Assistant` |

> `aurora-commerce` regroupe **2 sous-dossiers** (Ecommerce+Erp) → splitsh sait
> splitter plusieurs prefixes vers un repo, ou on structure un sous-dossier
> commun. Cas particulier, à valider au POC.

## 2. Anatomie d'un package module (ex. `aurora-tools`)

Le subtree `src/Module/Tools/` doit devenir un package autonome. Y ajouter
(dans le monorepo, sous `src/Module/Tools/`, pour que splitsh les emporte) :

```
src/Module/Tools/
├── composer.json            ← (nouveau) manifeste du package
├── config/
│   ├── services.php         ← (nouveau) services du module (Aurora\Module\Tools\)
│   └── routes.php           ← (nouveau) chargement des contrôleurs du module
├── AuroraToolsBundle.php     ← (existe) auto-enregistre Doctrine/Twig/i18n/RTE
├── Setting/
│   ├── ToolsModuleParameterEnum.php       ← (existe) toggles du module
│   └── ToolsModuleParameterProvider.php   ← (existe) settings (évite le wipe)
├── Vault/ … PasswordGenerator/ …          ← (existe) le code métier
├── translations/  templates/  assets/     ← (existe)
└── tests/                   ← (à déplacer) les tests du module
```

### 2.1 `composer.json` (template)

```json
{
    "name": "axelraboit/aurora-tools",
    "description": "Tools module (Vault, PasswordGenerator) for Aurora.",
    "type": "symfony-bundle",
    "license": "proprietary",
    "require": {
        "php": ">=8.4",
        "axelraboit/aurora-core": "self.version"
    },
    "autoload": {
        "psr-4": { "Aurora\\Module\\Tools\\": "" }
    },
    "extra": {
        "symfony": { "bundle": "Aurora\\Module\\Tools\\AuroraToolsBundle" }
    }
}
```

> `"Aurora\\Module\\Tools\\": ""` car splitsh extrait `src/Module/Tools/*` à la
> racine du repo enfant. À **valider au POC** (le mapping PSR-4 root est le
> point le plus à risque ; alternative : restructurer le subtree sous `src/`).

### 2.2 `config/services.php` (le bout que le monorepo ne peut pas activer)

Quand le package est seul, ceci ne crée **aucun** conflit de merge :

```php
return static function (ContainerConfigurator $c): void {
    $services = $c->services()->defaults()->autowire()->autoconfigure();
    $services->load('Aurora\\Module\\Tools\\', '../')
        ->exclude(['../{Setting/*Enum.php,*/Entity,*Bundle.php}']);
    // _instanceof local POUR LES interfaces que CE module implémente
    // (ConfigurationTabProvider, ApplicationParameterProvider, …) :
    $services->instanceof(ApplicationParameterProviderInterface::class)
        ->tag('aurora.application_parameter_provider');
    // … (uniquement les tags pertinents pour Tools)
};
```

Le `AuroraToolsBundle::loadExtension()` importe ce fichier. **Côté `aurora-core`**,
au split, retirer Tools du glob central (déjà simulé par `$extractedModules`).

### 2.3 `config/routes.php`

```php
return static function (RoutingConfigurator $routes): void {
    $routes->import('../', 'attribute'); // contrôleurs du module
};
```

## 3. Outillage split

- **`splitsh/lite`** (binaire/Docker) — utilisé par Symfony. Rapide, splitte
  l'historique git d'un sous-dossier.
- Alternative : `symplify/monorepo-builder` (Sylius) — plus de features
  (versioning synchronisé, `merge`), plus lourd.

Config indicative (`splitsh` via script, ou `monorepo-builder.php`) :

```
src/Module/Tools         → git@github.com:axelraboit/aurora-tools.git
src/Module/Notes         → git@github.com:axelraboit/aurora-notes.git
src/Module/Ecommerce+Erp → git@github.com:axelraboit/aurora-commerce.git
…
(le reste)               → git@github.com:axelraboit/aurora-core.git
```

Commande type (splitsh-lite) :
```bash
splitsh-lite --prefix=src/Module/Tools --target=heads/split-tools
git push aurora-tools split-tools:main
```

## 4. Ordre d'extraction (du plus simple au plus dur)

1. **POC** : `aurora-tools` (leaf pur, petit) — valide TOUTE la mécanique
   (composer.json, services.php, routes, PSR-4 root, install dans un
   `aurora-client` neuf, tests verts).
2. Leaves : Hr, Planning, Notes, PersonalFinance, Assistant.
3. Soft-ref : Photo, Editorial, Crm, Billing, Project.
4. Fusion : Commerce (Ecommerce+Erp) en dernier.

## 5. Validation par package (critères Phase 9.3)

Pour chaque package extrait :
- [ ] `composer require axelraboit/aurora-<x>` dans un `aurora-client` neuf.
- [ ] `php bin/console cache:clear` OK (bundle s'enregistre seul).
- [ ] `doctrine:schema:validate` OK ; `debug:router` montre les routes du module.
- [ ] `app:application-parameter` **ne wipe pas** les settings du module (provider présent).
- [ ] Toggles visibles dans `/dev/dashboard/modules` (registry).
- [ ] Tests du package verts en isolation.
- [ ] Extension Sylius-style depuis le client OK (étendre une entité, override un manager).
- [ ] Build Vite OK (selon stratégie assets — Gate 2, encore ouvert).

## 6. Migrations (Phase 5)

Les 3 migrations soft-ref (`down()` re-crée des FK CRM) ne sont valides que si
Crm installé. Choix à figer **avant** le rollout :
- (reco) **migrations côté client** : le client orchestre le schéma de tous ses
  packages installés ; chaque package fournit ses migrations, le client les joue.
- ou : repartir d'un schéma propre par package (perte d'historique).

## 7. Transition clients existants (Phase 11)

Reco = **Option C** : méta-package `axelraboit/aurora` marqué `deprecated`,
`require` tous les sous-packages en v2.0 pendant 1-2 versions, puis hard-cut.
Pattern Symfony.

## 8. Prérequis infra (décisions hors-code)

- [ ] Installer `splitsh-lite` (binaire ou Docker) — **absent localement**.
- [ ] Créer les repos GitHub enfants (`aurora-tools`, …) OU script de création.
- [ ] Accès Packagist / repo Composer privé pour publier.
- [ ] CI : pipeline par package (ou monorepo CI qui split + push à chaque tag).

## 9. Stratégie assets Vue (Gate 2 — TOUJOURS OUVERT)

Le seul gate non tranché. Le build Vite est aujourd'hui centralisé
(`public/build` 9.9 Mo). Les 3 options (A pré-buildé / B plugin Vite custom /
C symlinks post-install) restent à POC'er sur 1 module. **Bloquant** pour un
package front-complet ; les packages back-only peuvent partir sans.

---

**TL;DR** : le découplage est fini. Le packaging réel = boucle « ajouter
composer.json + config/{services,routes} au subtree → splitsh → install dans un
client neuf → valider » package par package, en commençant par `aurora-tools`.
Prérequis bloquants restants : **splitsh installé**, **repos GitHub**, et le
**Gate 2 assets** pour les packages avec front.
