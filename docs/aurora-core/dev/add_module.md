# Créer un nouveau module dans aurora-core

> **Commit de référence** : `167aafa` — `feat: PasswordGenerator module + vault password picker integration`
> Ce commit est l'implémentation canonique d'un module stateless simple. Lire son diff
> avant de commencer un nouveau module.

---

## 1. Quand créer un module ?

Un **module** regroupe un domaine métier cohérent sous `src/Module/<Module>/`. Il peut être :

- **Stateless** — pas d'entité propre, juste un controller et une UI (ex: PasswordGenerator).
- **Avec entités CRUD** — suit le pattern d'extensibilité en 5 couches (Entity/DTO/Manager/Serializer/Vue). Voir [`entity_extensibility_convention.md`](entity_extensibility_convention.md).

Cette doc couvre le cas **stateless**. C'est le minimum viable — tout module commence par là.

---

## 2. Checklist stateless

### PHP

#### 2.1 `<Module>Module.php` — inscription du module

```php
// src/Module/PasswordGenerator/PasswordGeneratorModule.php
final readonly class PasswordGeneratorModule implements ModuleInterface
{
    public function getId(): string { return 'password_generator'; }

    public function getPermissions(): array
    {
        return [new NavPermission('password_generator.use')];
    }

    public function getNavSections(): array  { return []; }
    public function getCatalogNavSections(): array { return []; }
}
```

**Règles :**
- Si le module a sa propre section nav (ex: CRM, Billing) → déclarer le `NavSection` + les `NavItem` ici.
- Si le module **rejoint une section existante** (ex: PasswordGenerator → section `vault`) → laisser `getNavSections()` vide et ajouter le `NavItem` dans le module propriétaire de la section.
- `getNavSections()` et `getCatalogNavSections()` sont toujours déclarées (interface complète), même si vides.
- Auto-découvert par Symfony via `_instanceof: ModuleInterface: tags: [aurora.module]` — **aucune registration manuelle**.

#### 2.2 Ajouter un NavItem dans la section propriétaire

```php
// src/Module/Vault/VaultModule.php
new NavSection('vault', [
    new NavItem('backend_vault', 'backend.nav.vault', 'vault', ...),
    new NavItem(
        route: 'backend_password_generator',
        labelKey: 'backend.nav.password_generator',
        icon: 'key-round',                        // clé dans ICON_MAP de useSidemenuNav.js
        requiredPrivilege: 'password_generator.use',
        descriptionKey: 'backend.nav.password_generator_description',
    ),
], priority: 20),
```

Si une icône manque dans `ICON_MAP` → l'ajouter dans `assets/Core/backend/sidemenu/composables/useSidemenuNav.js`.

#### 2.3 Controller

```php
// src/Module/PasswordGenerator/Controller/Backend/PasswordGeneratorController.php
#[Route('/backend/password-generator', name: 'backend_password_generator')]
#[IsGranted('password_generator.use')]
final class PasswordGeneratorController extends AbstractController
{
    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@PasswordGenerator/backend/index.html.twig');
    }
}
```

**Règles :** `final class` (pas `final readonly`) pour les controllers. Injecter un `ViewBuilder` si des données serveur sont nécessaires.

#### 2.4 Traductions

```yaml
# src/Module/PasswordGenerator/translations/messages.fr.yaml
backend:
  permissions:
    names:
      password_generator:
        use: Utiliser le générateur de mots de passe
  modules:
    password_generator: Générateur de mots de passe
  nav:
    password_generator: Générateur de mots de passe
    password_generator_description: Générez des mots de passe forts et aléatoires

password_generator:
  title: Générateur de mots de passe
  # ... autres clés UI
```

**Auto-découvert** : le translator path `src/Module/PasswordGenerator/translations/` est enregistré automatiquement par glob dans `AuroraBundle`.

---

### Auto-découverte — zéro wiring manuel

| Chose | Mécanisme |
|---|---|
| Service Symfony | `Aurora\: resource: '../src/'` dans services.yaml |
| Tag `aurora.module` | `_instanceof: ModuleInterface` dans services.yaml |
| Namespace Twig `@PasswordGenerator` | glob `src/Module/*` + `templates/Module/PasswordGenerator/` |
| Paths traductions | glob `src/Module/*/translations/` dans AuroraBundle |
| `DumpJsTranslationsCommand` | même glob |
| Composants Vue | glob `assets/Module/**/*.vue` dans assets/app.js |

**Seul wiring manuel** : `resolve_target_entities` dans `AuroraBundle.php` — uniquement si le module a des entités Doctrine.

---

### Template Twig

```twig
{# templates/Module/PasswordGenerator/backend/index.html.twig #}
{% extends '@Core/backend/layout.html.twig' %}

{% block title %}{{ 'backend.nav.password_generator'|trans }} - {{ parent() }}{% endblock %}

{% block page_header_slot %}
    {{ include('@Shared/components/page_header.html.twig', {
        crumbs: [
            {label: 'backend.nav.sections.vault'|trans},
            {label: 'backend.nav.password_generator'|trans},
        ],
    }) }}
{% endblock %}

{% block body %}
<div {{ vue_component('passwordgenerator/backend/PasswordGeneratorApp', {}) }} class="flex-1 min-w-0"></div>
{% endblock %}
```

Le nom du composant dans `vue_component()` = nom du module **en minuscules** + chemin relatif depuis `assets/Module/<Module>/`.

---

### Vue + alias Vite

**Ajouter l'alias dans `aliases.js`** (source unique partagée entre Vite et Vitest) :

```js
// aliases.js
"@password-generator": moduleAlias("PasswordGenerator"),
```

C'est **tout**. Vite et Vitest récupèrent l'alias automatiquement.

**Composable :**

```js
// assets/Module/PasswordGenerator/backend/composables/usePasswordGenerator.js
export function usePasswordGenerator() {
    // logique métier ici
    return { length, options, password, generate, copy };
}
```

**Composant principal :**

```vue
<!-- assets/Module/PasswordGenerator/backend/PasswordGeneratorApp.vue -->
<script setup>
import { usePasswordGenerator } from '@password-generator/backend/composables/usePasswordGenerator.js';
const { ... } = usePasswordGenerator();
</script>
```

---

## 3. Icônes de navigation

Les icônes nav sont des chaînes résolues via `ICON_MAP` dans `useSidemenuNav.js`. Si l'icône manque → fallback sur `FileText`. Ajouter l'import lucide + l'entrée dans `ICON_MAP` au besoin.

```js
// assets/Core/backend/sidemenu/composables/useSidemenuNav.js
import { KeyRound } from 'lucide-vue-next';

const ICON_MAP = {
    // ...
    'key-round': KeyRound,
};
```

---

## 4. Module avec entités CRUD

Pour un module qui gère des entités persistées, suivre en plus :
1. Checklist CLAUDE.md §8 (Entity, DTO, Manager, Serializer, Controller, Vue)
2. Ajouter à `resolve_target_entities` dans `AuroraBundle.php`
3. Doc de référence : [`entity_extensibility_convention.md`](entity_extensibility_convention.md)
