---
name: convention-overrides-vs-modules
description: Where to place client-side Vue files — three buckets under `src/` (Module, Overrides, Module/Platform/<X>/ PHP-only) each with a different glob and Twig exposure. Why the override Vue for Agency lives in src/Overrides/ and not src/Module/Platform/Agency/assets/.
metadata:
  type: project
---

# Convention : 3 buckets pour le code client sous `src/`

Aurora-client expose **trois emplacements distincts** sous `src/`, chacun
avec son propre rôle, son propre glob Vue, et sa propre convention d'appel
côté Twig. Le placement n'est pas interchangeable.

## Les 3 buckets

| Bucket | Contenu | Glob Vue (app.js) | Exposition Twig | Cas d'usage |
|---|---|---|---|---|
| **`src/Module/<X>/`** (avec `assets/`) | Module client autonome — son propre domaine, son propre NavItem, ses propres permissions | `@client/src/Module/*/assets/**/*.vue` → `<x>/...` | `vue_component('<x>/backend/Foo')` | Tracking, Loyalty, n'importe quel module créé via `aurora:make:module` |
| **`src/Overrides/`** | Wrappers autour des composants Vue d'Aurora — shadow direct, **pas** de NavItem | `@client/src/Overrides/**/*.vue` → `<path-sans-prefix>` | `vue_component('backend/Foo')` *sans préfixe module* | Extension visuelle d'une entité Aurora (AgenciesApp wrapper qui passe `extraFields`) |
| **`src/Module/<X>/` PHP-only** (sans `assets/`) | Extension Doctrine/DI d'une entité Aurora — Sylius layer 1-4 (Entity + DTO + Manager + Serializer) | _(n/a, pas de Vue ici)_ | _(n/a, l'override Vue vit dans `src/Overrides/`)_ | `src/Module/Platform/Agency/{Entity,Dto,Manager,Serializer}/` |

## Pourquoi le split entre Module et Overrides

**Why:** Si on mettait `AgenciesApp.vue` (wrapper qui override Aurora) sous
`src/Module/Platform/Agency/assets/backend/AgenciesApp.vue`, le glob
`Module/*/assets/**/*.vue` l'exposerait comme
`vue_component('platform/backend/agencies/AgenciesApp')`. Mais Aurora rend
le template `@Platform/backend/agencies/index.html.twig` qui appelle
`vue_component('backend/agencies/AgenciesApp')` — **sans le préfixe
`platform/`**. Du coup ton wrapper ne serait jamais résolu à la place du
composant Aurora — l'override échoue silencieusement.

Le bucket `Overrides/` distinct existe pour cette raison : son glob expose
les composants **sans préfixe de module**, ce qui permet le shadow direct
des composants Aurora.

**How to apply:**

- **Tu crées un nouveau module** (admin propre, NavItem, permissions) :
  `src/Module/<Module>/` avec `assets/backend/<Module>App.vue`. Le maker
  `aurora:make:module` fait ça automatiquement.
- **Tu wraps un composant Aurora** (passes `extraFields`, override la table,
  ajoutes des champs custom au form) : `src/Overrides/backend/<plural>/<Name>App.vue`.
  Le wrapper Vue importe le composant Aurora via `@core/...` ou `@<module>/...`
  et le consomme.
- **Tu étends une entité Aurora** côté serveur (Entity, DTO, Manager,
  Serializer) : `src/Module/Platform/Agency/...` ou `src/Module/<AuroraModule>/<Entity>/...`.
  **Pas de dossier `assets/`** dans ce cas — l'override Vue va dans `src/Overrides/`,
  pas ici.

## Exemple complet : étendre Agency avec un champ `code`

```
src/Module/Platform/Agency/              ← extension serveur (PHP)
├── Entity/Agency.php                    ← extends AbstractAgency
├── Dto/AgencyInput.php                  ← extends AgencyInput aurora
├── Dto/AgencyInputFactory.php           ← #[AsAlias(AgencyInputFactoryInterface)]
├── Manager/AgencyManager.php            ← override create<X>() + applyInput()
└── Serializer/AgencySerializer.php      ← override serialize() avec spread parent

src/Overrides/backend/agencies/          ← extension Vue (côté front)
└── AgenciesApp.vue                       ← wrapper qui passe extraFields + slots scoped
```

Le wrapper Vue importe le composant Aurora :

```vue
<!-- src/Overrides/backend/agencies/AgenciesApp.vue -->
<script setup>
import AuroraAgenciesApp from '@platform/backend/agencies/AgenciesApp.vue';
// (ou @core/backend/agencies/AgenciesApp.vue selon comment Aurora structure)

const extraFields = {
    code: { default: '', fromEntity: (a) => a.code ?? '' },
};
</script>

<template>
    <AuroraAgenciesApp v-bind="$attrs" :extra-fields="extraFields">
        <template #extra-headers>...</template>
        <template #extra-cells="{ agency }">...</template>
        <template #extra-form-fields="{ form, errors }">...</template>
    </AuroraAgenciesApp>
</template>
```

Côté Twig admin, Aurora rend `vue_component('backend/agencies/AgenciesApp', ...)`.
Sans préfixe, le glob `Overrides/**/*.vue` résout ton wrapper avant le
composant Aurora — l'override prend.

## Anti-patterns

- ❌ Mettre `AgenciesApp.vue` override sous `src/Module/Platform/Agency/assets/`
  → le préfixe `platform/` casse le shadow direct
- ❌ Mettre un module client autonome sous `src/Overrides/` → l'absence de
  préfixe brouille le namespace, et le composant sera shadow par un futur
  changement aurora-core
- ❌ Confondre `src/Overrides/` avec `templates/Module/<X>/` (legacy
  layout pre-9d77f67) — `templates/` est pour les **templates Twig**, pas
  les Vue components

## Source

- `vendor/.../src/Core/Frontend/app.js` — les 3 globs `auroraModules`,
  `clientModules`, `clientOverrides` (cf. les commentaires au-dessus de
  chaque glob)
- `aurora-client` commit `9d77f67` — refactor qui a déplacé
  `assets/client/Overrides/` (root) vers `src/Overrides/`
- Lien : [[pattern_extend_vue]] pour le pattern de wrapper Vue lui-même
