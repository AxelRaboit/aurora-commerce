---
name: NavPermission $group override for cross-module section display
description: Quand une permission déclarée par un module doit apparaître sous une section différente dans la modale Privilèges (ex CoreModule's permissions surfacées sous "Plateforme")
type: project
---

## Règle

`NavPermission` accepte un paramètre optionnel `$group` (string). Quand
défini, il **override le module-id par défaut** utilisé pour grouper la
permission dans la modale Privilèges.

```php
new NavPermission('core.media.view', group: 'platform'),
```

Le `PermissionRegistry::byModule()` indexera cette permission sous
`'platform'` au lieu de `'core'` (le `getId()` de `CoreModule`).

## Pourquoi

Un module class PHP peut être organisationnellement responsable de
permissions qui appartiennent **conceptuellement** à une autre section
d'UI. Cas typique :

- `CoreModule` (un seul `ModuleInterface`) déclare les permissions
  `core.media.*`, `core.users.*`, `core.agencies.*`, `core.services.*`,
  `core.settings.*`, `core.themes.*`
- Ces fonctionnalités sont visibles dans la **section "Plateforme"** du
  sidemenu (priority 20), pas dans une section "Cœur"
- Sans `$group`, la modale Privilèges afficherait un groupe "Cœur" avec
  toutes ces permissions, sans correspondance avec ce que voit l'admin
  dans le sidemenu
- Avec `$group: 'platform'`, elles apparaissent sous "Plateforme",
  cohérent avec le sidemenu

## Comment l'appliquer

### Quand l'utiliser

- Le module class PHP **regroupe** plusieurs sections d'UI (cas
  `CoreModule` = core + platform + dev)
- La permission appartient conceptuellement à une de ces sous-sections

### Quand NE PAS l'utiliser

- La permission appartient au module qui la déclare (cas normal — laisser
  `$group` à null, le `getId()` du module fait le job)
- Pour ranger artificiellement une permission ailleurs juste pour faire
  joli — c'est du déplacement organisationnel, pas du structurel

### Exemple complet

```php
// CoreModule.getPermissions()
return [
    // Général — section "Général" dans le sidemenu
    new NavPermission('general.dashboard.view', group: 'general'),

    // Plateforme — section "Plateforme" dans le sidemenu
    new NavPermission('core.media.view', group: 'platform'),
    new NavPermission('core.media.manage', group: 'platform'),
    new NavPermission('core.users.manage', group: 'platform'),
    new NavPermission('core.users.modules.manage', group: 'platform'),
    new NavPermission('core.agencies.manage', group: 'platform'),
    new NavPermission('core.services.manage', group: 'platform'),
    new NavPermission('core.settings.manage', group: 'platform'),
    new NavPermission('core.themes.manage', group: 'platform'),

    // Pas de groupe explicite → reste sous CoreModule's getId() = 'core'
    // (transverse, pas une section de sidemenu)
    new NavPermission('core.search.view'),
];
```

### Côté ordre d'affichage

`UsersViewBuilder::MODULE_PRIORITY` ordonne les groupes dans la modale
Privilèges (et la modale Modules) en miroir des priorités NavSection.
Si on introduit un nouveau group via `$group`, **penser à l'ajouter à
cette map** sinon il finira en bas avec `UNKNOWN_PRIORITY = 500`.

## Lieux clés

- VO : `src/Core/Module/NavPermission.php` (paramètre `$group`)
- Indexing : `src/Core/Module/PermissionRegistry.php` (utilise
  `permission->group ?? module->getId()`)
- Ordre d'affichage : `src/Core/User/View/UsersViewBuilder.php`
  (`MODULE_PRIORITY` const)
- Traductions : chaque group doit avoir `backend.modules.<group>`
  (label) — y compris les groups dérivés (`general`, `platform` à côté
  du `core` historique)
