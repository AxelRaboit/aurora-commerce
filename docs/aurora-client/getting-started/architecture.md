# Architecture — Structure du projet

## Vue d'ensemble

Aurora-client est une **application Symfony cliente** qui consomme `axelraboit/aurora`
(aurora-core) comme package Composer. Aurora-core fournit toute l'infrastructure
(modules métier, entités, UI admin, assets Vue) — aurora-client n'écrit que le **delta** :
les extensions, les modules propres au projet, les overrides.

```
aurora-client/
├── src/                    # Code PHP client
│   ├── Module/             # TOUT le code client (extensions + modules propres)
│   │   ├── Core/           #   Extensions d'entités Aurora\Core\*
│   │   │   └── Agency/     #     e.g. {Entity,Dto,Manager,Serializer}
│   │   ├── Crm/            #   Extensions d'entités Aurora\Module\Crm\*
│   │   └── Tracking/       #   Module métier propre au client
│   ├── Service/            # Services cross-modules stateless (rare)
│   └── EventListener/      # Listeners globaux (rare)
├── assets/client/          # Assets Vue côté client
│   ├── Module/             # Composants pour les modules client
│   │   └── Tracking/       # Composants du module Tracking
│   ├── Overrides/          # Composants qui remplacent des composants Aurora
│   └── locales/            # Traductions Vue-only (en.js, fr.js)
├── templates/              # Templates Twig qui surchargent Aurora
│   ├── Core/               # Overrides de templates Core Aurora
│   └── Module/             # Templates des modules client
├── config/                 # Configuration Symfony
│   ├── packages/           # YAML bundles (doctrine, twig, security…)
│   ├── routes.yaml         # Chargement des routes (Aurora + client)
│   └── services.yaml       # Enregistrement des services + modules
├── migrations/             # Migrations Doctrine propres au client
└── vendor/axelraboit/aurora/  # Aurora-core (lecture seule, jamais modifié)
```

---

## Relation avec aurora-core

```
aurora-client  ──uses──►  vendor/axelraboit/aurora  (aurora-core)
                                │
                                ├── src/              bundle PHP + JS/Vue co-localisés
                                │   ├── Core/Frontend/  cross-cutting JS/Vue/CSS
                                │   └── Module/<X>/assets/  per-module JS/Vue
                                ├── templates/        templates Twig
                                ├── docs/             documentation
                                └── .claude/memory/   mémoires Claude
```

**Règle d'or** : ne jamais modifier de fichier sous `vendor/`. Toute
personnalisation passe par les points d'extension d'Aurora (héritage,
`#[AsAlias]`, slots Vue, override Twig). Voir [../extending/extend_module.md](../extending/extend_module.md).

---

## Configuration Doctrine

`config/packages/doctrine.yaml` déclare un seul mapping couvrant tout `src/Module/` :

```yaml
doctrine:
    orm:
        mappings:
            AuroraClient:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src/Module'
                prefix: 'App\Module'
                alias: AuroraClient
        resolve_target_entities:
            Aurora\Module\Platform\Agency\Entity\AgencyInterface: App\Module\Platform\Agency\Entity\Agency
```

- **AuroraClient** — couvre tout `src/Module/` : extensions Aurora ET modules propres au client
- **resolve_target_entities** — substitue une interface Aurora par l'entité cliente (chemin miroir)

---

## Enregistrement des modules

Dans `config/services.yaml`, chaque module client est taggé `aurora.module` :

```yaml
App\Module\Tracking\TrackingModule:
    tags: [aurora.module]
```

Si le module a une partie frontend publique, il implémente aussi `FrontendInterface` :

```yaml
App\Module\Tracking\Frontend\TrackingFrontend:
    tags: [aurora.front]
```

Aurora collecte tous les services taggés et les intègre automatiquement dans la
sidemenu admin, le système de permissions et le routing frontend.

---

## Chargement des assets

`assets/client/` est mappé à l'alias `@client` dans Vite. Aurora scanne
`@client/Module/**/*.vue` et enregistre les composants avec la même convention
que ses propres modules :

```
assets/client/Module/Tracking/admin/ProjectsApp.vue
→ vue_component('tracking/admin/ProjectsApp')
```

Les overrides de composants Aurora vivent sous `assets/client/Overrides/` :

```
assets/client/Overrides/backend/agencies/AgenciesApp.vue
→ remplace le composant Aurora 'core/backend/agencies/AgenciesApp'
```

---

## Chargement des templates

Aurora prepend automatiquement les paths côté projet client devant ses
propres paths pour chaque namespace Twig (`@Core`, `@Shared`, `@Editorial`,
`@Crm`, etc.). Deux paths d'override sont reconnus :

- **Nouveau** (recommandé, aligné sur la convention core) :
  `src/Core/templates/Core/backend/agencies/index.html.twig`,
  `src/Module/<X>/templates/...`, etc.
- **Legacy** (backward compat) : `templates/Core/backend/agencies/index.html.twig`,
  `templates/Module/<X>/...`, etc.

L'un ou l'autre surcharge automatiquement
`vendor/axelraboit/aurora/src/Core/templates/Core/backend/agencies/index.html.twig`
sans configuration Twig supplémentaire.

---

## Translations

Les traductions client suivent deux canaux :

| Canal | Fichiers | Usage |
|---|---|---|
| Symfony (Twig/PHP) | `src/Module/*/translations/messages.{fr,en}.yaml` | Labels admin, emails, validations |
| Vue (vue-i18n) | `assets/client/locales/{fr,en}.js` | Labels Vue-only (boutons, permissions UI) |

Enregistrement dans `config/services.yaml` :

```yaml
App\Core\Command\DumpJsTranslationsCommand:
    arguments:
        $sourceDirs:
            - '%kernel.project_dir%/src/Module/Tracking/translations'
```

Après avoir modifié un YAML de traduction :

```bash
make translation   # régénère src/Core/Frontend/locales/generated/*.json + clear cache
```
