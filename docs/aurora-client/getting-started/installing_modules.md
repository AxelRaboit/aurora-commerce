# Installer des modules Aurora à la carte (sans Packagist)

Chaque module Aurora est un package Composer indépendant
(`axelraboit/aurora-<module>`) hébergé sur son propre repo GitHub. Un client
n'installe **que** ce dont il a besoin. Aucune publication Packagist requise :
on passe par des dépôts VCS.

> Le découpage vit dans le monorepo `aurora-core` ; chaque package est extrait
> par `bin/split-modules.sh`. Côté client, l'install se résume à 4 points (dont
> 2 sont auto-découverts).

## 1. Déclarer les dépôts VCS + requérir les packages

Dans le `composer.json` du client :

```json
{
    "minimum-stability": "dev",
    "prefer-stable": true,
    "require": {
        "axelraboit/aurora": "dev-develop",
        "axelraboit/aurora-tools": "dev-main",
        "axelraboit/aurora-crm": "dev-main"
    },
    "repositories": [
        { "type": "vcs", "url": "git@github.com:AxelRaboit/aurora-core.git" },
        { "type": "vcs", "url": "git@github.com:AxelRaboit/aurora-tools.git" },
        { "type": "vcs", "url": "git@github.com:AxelRaboit/aurora-crm.git" }
    ]
}
```

> Sans Packagist, **chaque** package installé a besoin de son entrée
> `repositories` (Composer n'utilise que les `repositories` du projet racine).
> C'est la seule liste à maintenir par module.

`composer update axelraboit/*` — terminé pour la partie packages.

## 2. Bundles — auto-découverts (zéro édition par module)

Le `config/bundles.php` du client spread les bundles de **tous** les packages
`aurora-*` installés. Installer/désinstaller un module = `composer require/remove`,
rien à toucher ici :

```php
// config/bundles.php
return [
    Aurora\AuroraBundle::class => ['all' => true],
    ...Aurora\Core\Bundle\AuroraModuleBundles::all(\dirname(__DIR__)),
    // ... bundles framework ...
];
```

`AuroraModuleBundles::all()` scanne `vendor/axelraboit/aurora-*/composer.json`
et lit `extra.aurora.bundles` (array, ex. `aurora-commerce`) ou
`extra.symfony.bundle`. `aurora-core` est ignoré (son bundle est listé à part).

## 3. Routes — auto-découvertes (une seule entrée)

```yaml
# config/routes.yaml
aurora:
    resource: '../vendor/axelraboit/aurora/src/'
    type: attribute

aurora_modules:
    resource: .
    type: aurora_modules   # loader fourni par aurora-core
```

`aurora_modules` (cf. `Aurora\Core\Routing\AuroraModuleRouteLoader`) importe les
contrôleurs de chaque package `vendor/axelraboit/aurora-*` installé. Une entrée,
quel que soit le nombre de modules.

## 4. Assets Vue — automatiques

Rien à faire : le build Vite d'aurora-core (`vite-plugin-aurora-modules.js` +
`aliases.js` vendored-aware) découvre les composants Vue des packages installés
sous `vendor/axelraboit/aurora-*`. `make build` côté client les bundle comme s'ils
étaient first-party. Voir Gate 2 (option B) dans
`docs/aurora-core/dev/audit/packaging_playbook.md`.

## Désinstaller un module

`composer remove axelraboit/aurora-<module>` + retirer son entrée
`repositories`. Les bundles, routes et assets disparaissent automatiquement
(auto-découverte). Penser à gérer le schéma DB (migrations) si le module avait
des tables.

## Cas particulier : `aurora-commerce`

`Ecommerce` + `Erp` sont **inséparables** (les contrôleurs Ecommerce
dépendent du `ProductRepository` concret d'Erp) → un seul package
`axelraboit/aurora-commerce` qui embarque les deux (sous-dossiers `Ecommerce/`
+ `Erp/`, deux bundles déclarés via `extra.aurora.bundles`). On l'installe d'un
bloc.
