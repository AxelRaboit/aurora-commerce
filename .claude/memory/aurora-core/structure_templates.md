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
- `<Module>/frontend/<page>.html.twig` (snake_case dans le path).
- Les pages frontend héritent souvent d'un layout dans `Shared/` ou
  `Frontend/themes/<theme>/layout.html.twig`.

### Emails
- `<Module>/email/<event>.html.twig` (ex: `Crm/email/deal_stage_changed.html.twig`).
- Hérite de `Shared/email/layout/email.html.twig`.
- Snake_case dans le nom de fichier.

## Pattern admin page (Vue mount)

L'admin Aurora utilise Twig comme **shell** qui mount un composant Vue.
Pattern type :

```twig
{# templates/Core/backend/agencies/index.html.twig #}
{% extends '@Core/backend/layout.html.twig' %}

{% block title %}{{ 'backend.agencies.title' | trans }}{% endblock %}

{% block body %}
    <div
        data-controller="vue-mount"
        data-vue-mount-component-value="AgenciesApp"
        data-vue-mount-props-value="{{ {
            agencies: agencies,
            createPath: createPath,
            updatePath: updatePath,
            deletePath: deletePath,
        } | json_encode | escape('html_attr') }}"
    ></div>
{% endblock %}
```

Le Stimulus controller `vue-mount` instancie le composant Vue avec les
props passées par le ViewBuilder. **Pas de logique inline en Twig** pour
les pages admin SPA.

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
