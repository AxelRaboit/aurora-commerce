# Templates Twig — convention de structure

## Règle

```
templates/
├── Core/
│   ├── backend/       ← admin pages (auth required)
│   │   ├── layout.html.twig
│   │   ├── base_guest.html.twig
│   │   ├── <plural>/index.html.twig    ← une page par entité (au pluriel)
│   │   └── …
│   ├── email/         ← templates email Core
│   └── frontend/      ← public pages Core
├── Module/
│   └── <Module>/
│       ├── backend/   ← admin pages du module
│       │   └── <plural>/index.html.twig
│       ├── frontend/  ← public pages du module (gallery viewer, etc.)
│       └── email/     ← email templates du module
├── Frontend/
│   └── themes/        ← thèmes frontend (le client peut ajouter ses thèmes)
├── Shared/
│   ├── components/    ← partials Twig réutilisables (boutons, cards, …)
│   ├── email/         ← layout + partials email
│   │   ├── layout/
│   │   └── partials/
│   └── …
└── bundles/
    └── TwigBundle/    ← override de templates third-party (error pages, …)
```

## Namespaces Twig

| Namespace | Path |
|---|---|
| `@Core` | `templates/Core/` |
| `@Editorial` | `templates/Module/Editorial/` |
| `@Crm` | `templates/Module/Crm/` |
| `@Erp` | `templates/Module/Erp/` |
| `@Project` | `templates/Module/Project/` |
| `@Photo` | `templates/Module/Photo/` |
| `@Billing` | `templates/Module/Billing/` |
| `@Ecommerce` | `templates/Module/Ecommerce/` |
| `@Ged` | `templates/Module/Ged/` |
| `@Frontend` | `templates/Frontend/` |
| `@Shared` | `templates/Shared/` |

## Override automatique côté client

Le bundle Aurora prepend `kernel.project_dir/templates/<Namespace>/`
devant son propre chemin sous chaque namespace. Un override client met
juste son fichier au chemin miroir → résolu en priorité.

Ex: `vendor/axelraboit/aurora/templates/Core/backend/agencies/index.html.twig`
peut être overridé par `templates/Core/backend/agencies/index.html.twig`
côté client, et `@Core/backend/agencies/index.html.twig` résoudra le
fichier client en priorité.

Cf [`client/pattern_override_twig.md`](client/pattern_override_twig.md)
pour les détails côté client.

## Système de thèmes frontend

`ThemeResolver::resolve('editorial/post/index')` :
1. Cherche `templates/Frontend/themes/<slug-actif>/editorial/post/index.html.twig`
2. Si trouvé → l'utilise. Sinon → fallback sur `default/`

Exemples de chemins résolus :
- `ecommerce/shop/category` → `themes/<slug>/ecommerce/shop/category.html.twig`
- `ecommerce/shop/tag` → `themes/<slug>/ecommerce/shop/tag.html.twig`
- `editorial/post/show` → `themes/<slug>/editorial/post/show.html.twig`

Le thème actif est lu en BDD (`core_themes WHERE active = true`).
**Un seul thème actif à la fois.** Un thème custom n'override que les templates qu'il contient — tout le reste tombe sur `default`.

### Créer et activer un thème custom

```bash
# 1. Créer le dossier + copier uniquement les templates à modifier
mkdir -p templates/Frontend/themes/mon-theme/
cp templates/Frontend/themes/default/layout.html.twig templates/Frontend/themes/mon-theme/

# 2. Insérer en BDD
php bin/console dbal:run-sql "INSERT INTO core_themes (id, slug, name, active, config) VALUES (NEXTVAL('seq_core_theme_id'), 'mon-theme', 'Mon Thème', false, '{}')"

# 3. Activer (désactiver les autres d'abord)
php bin/console dbal:run-sql "UPDATE core_themes SET active = false"
php bin/console dbal:run-sql "UPDATE core_themes SET active = true WHERE slug = 'mon-theme'"
```

Aussi gérables depuis `/backend/themes`.

### `resolveAll()`

Retourne une map `nom → chemin résolu` pour les templates de pages + le layout
de chaque thème. Les anciens partials `editorial/_post_card` et
`editorial/_pagination` ont été supprimés (remplacés par les composants Vue
`PostCard.vue` et `AppPagination`), donc `resolveAll()` ne pointe plus vers ces
fichiers — il ne contient que les pages-passerelles et le layout.

**Doc canonique** : [`docs/aurora-core/dev/frontend_theme_override.md`](../../../docs/aurora-core/dev/frontend_theme_override.md)

## Conventions de naming

### Pages admin
- `<Module>/backend/<plural>/index.html.twig` : page liste + form admin
  d'une entité (ex: `Core/backend/agencies/index.html.twig`).
- `<Module>/backend/<plural>/show.html.twig` : page détail (rare —
  souvent un overlay Vue suffit).
- `<Module>/backend/<plural>/edit.html.twig` : page edit dédiée si trop
  complexe pour un modal (ex: `Editorial/backend/posts/edit.html.twig`
  pour PostEditor full-page).

### Pages frontend
- **Folder-per-feature** quand le feature a ≥1 template :
  `<Module>/frontend/<feature>/index.html.twig`, `…/<feature>/category.html.twig`, etc.
  Ex : `Ecommerce/frontend/shop/{index,category,tag,product}.html.twig`,
  `Editorial/frontend/post/{index,show}.html.twig`.
- **Plat** quand single-file : `<Module>/frontend/<page>.html.twig`
  (ex : `Ecommerce/frontend/cart.html.twig`).
- Les pages frontend héritent du layout du thème
  (`Frontend/themes/<theme>/layout.html.twig`).
- Tous les templates frontend sont des passerelles Vue
  (cf [[convention_frontend_rendering]]).

### Emails
- `<Module>/email/<event>.html.twig` (ex: `Crm/email/deal_stage_changed.html.twig`).
- Hérite de `Shared/email/layout/email.html.twig`.
- Snake_case dans le nom de fichier.

## Pattern admin page (Vue mount)

L'admin Aurora utilise Twig comme **shell** qui mount un composant Vue
via le helper Twig `vue_component(...)` — **même pattern que le frontend**
(cf [[convention_frontend_rendering]]). Pattern type :

```twig
{# templates/Core/backend/agencies/index.html.twig #}
{% extends '@Core/backend/layout.html.twig' %}

{% block title %}{{ 'backend.agencies.title' | trans }}{% endblock %}

{% block body %}
<div {{ vue_component('core/backend/agencies/AgenciesApp', {
    agencies: agencies,
    createPath: createPath,
    updatePath: updatePath,
    deletePath: deletePath,
}) }}></div>
{% endblock %}
```

Le helper `vue_component()` sérialise les props en attributs data et
enregistre le mount. **Pas de logique inline en Twig** pour les pages
admin SPA — toutes les données viennent du `*ViewBuilder`.

> Note : l'ancien pattern `data-controller="vue-mount"` avec props
> `json_encode | escape('html_attr')` n'est plus utilisé. Tout est passé
> par `vue_component()` depuis le passage au composant unique de mount.

## Anti-patterns

- ❌ Templates dans le mauvais namespace (ex: page Crm sous `templates/Core/`).
- ❌ Logique métier en Twig (calculs, requêtes via filtres custom). Tout
  passe par le ViewBuilder.
- ❌ Mélanger admin (Stimulus + Vue) et frontend (pure Twig) dans le même
  dossier.
- ❌ Snake_case puis camelCase incohérent dans les paths
  (`Crm/Backend/Deals/` vs `Crm/backend/deals/`). On utilise **lowercase**
  pour les sous-dossiers fonctionnels (`backend/`, `frontend/`, `email/`,
  `<plural>/`).

## Twig CS Fixer

Le projet utilise `twig-cs-fixer` (cf `tools/twig-cs-fixer/`). Avant un
commit qui touche des templates :

```bash
make twig-cs-fix  # ou php vendor/.../twig-cs-fixer fix templates/
```
