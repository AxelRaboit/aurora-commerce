# Assets Vue — Composants côté client

## Structure

```
assets/client/
├── Module/
│   └── <ModuleName>/
│       ├── admin/
│       │   └── <Feature>App.vue      # composant admin du module
│       └── frontend/
│           └── <Feature>App.vue      # composant frontend public
├── Overrides/
│   └── backend/
│       └── <feature>/
│           └── <Feature>App.vue      # remplace un composant Aurora
└── locales/
    ├── fr.js                          # traductions Vue-only (FR)
    └── en.js                          # traductions Vue-only (EN)
```

---

## Conventions de nommage

Les composants sont enregistrés automatiquement par Aurora selon leur chemin :

| Fichier | Identifiant vue_component |
|---|---|
| `assets/client/Module/Tracking/admin/ProjectsApp.vue` | `tracking/admin/ProjectsApp` |
| `assets/client/Overrides/backend/agencies/AgenciesApp.vue` | `core/backend/agencies/AgenciesApp` |

Dans Twig :

```twig
{{ vue_component('tracking/admin/ProjectsApp') }}
{{ vue_component('core/backend/agencies/AgenciesApp') }}
```

---

## Aliases Vite disponibles

Source de vérité : `aliases.js` à la racine du repo aurora-core (consommé
par Vite et Vitest). Côté client, `make sync-jsconfig` régénère
`jsconfig.json` à partir de cette source.

| Alias | Chemin (côté client : préfixer par `vendor/axelraboit/aurora/`) |
|---|---|
| `@` | `assets/` |
| `@core` | `assets/Core/` |
| `@shared` | `assets/shared/` |
| `@editorial` | `assets/Module/Editorial/` |
| `@crm` | `assets/Module/Crm/` |
| `@erp` | `assets/Module/Erp/` |
| `@ecommerce` | `assets/Module/Ecommerce/` |
| `@photo` | `assets/Module/Photo/` |
| `@billing` | `assets/Module/Billing/` |
| `@ged` | `assets/Module/Ged/` |
| `@hr` | `assets/Module/Hr/` |
| `@planning` | `assets/Module/Planning/` |
| `@project` | `assets/Module/Project/` |
| `@notes` | `assets/Module/Notes/` |
| `@assistant` | `assets/Module/Assistant/` |
| `@vault` | `assets/Module/Vault/` |
| `@password-generator` | `assets/Module/PasswordGenerator/` |
| `@client` | `assets/client/` (uniquement côté client) |

Quand un module est ajouté côté core, ajouter l'alias dans `aliases.js`
puis lancer `make sync-jsconfig` côté client pour propager.

---

## Composants partagés Aurora (`@shared`)

Toujours utiliser les composants `App*` d'Aurora plutôt que les éléments HTML bruts.

| Composant | Usage |
|---|---|
| `AppInput` | Champ texte (`<input>`) |
| `AppTextarea` | Zone de texte (`<textarea>`) |
| `AppSelect` | Liste déroulante (`<select>`) |
| `AppButton` | Bouton (`<button>`) |
| `AppDatePicker` | Sélecteur de date (jamais `type="date"` natif) |
| `AppModal` | Modale (API `:show` + `@close`, jamais `v-model:open`) |
| `AppCheckbox` | Case à cocher |
| `AppBadge` | Badge statut |

Import :

```vue
<script setup>
import AppInput from '@shared/components/AppInput.vue';
import AppButton from '@shared/components/AppButton.vue';
import AppModal from '@shared/components/AppModal.vue';
</script>
```

---

## Conventions Vue

### Directives

```vue
<!-- ✅ Correct -->
<button v-on:click="handleClick">...</button>
<div :class="{ active: isActive }">...</div>

<!-- ❌ Éviter -->
<button @click="handleClick">...</button>
```

### Privacy dans les composables

```js
// ✅ Variable module-level non exportée (pas de préfixe _)
const cache = new Map();

export function useMyComposable() {
  // ...
}
```

```js
// ✅ Champs privés dans les classes
class MyService {
  #config;
  constructor(config) { this.#config = config; }
}
```

### Formulaires (`editForm`)

`editForm` ne doit contenir **que** les champs envoyés au backend :

```js
// ✅ Correct
const editForm = reactive({
  title: '',
  description: '',
});

// Submit
await request(url, { ...editForm });  // spread — envoie tout
```

```js
// ❌ Éviter — champs parasites
const editForm = reactive({
  title: '',
  isLoading: false,    // état UI — ne pas mettre ici
  displayLabel: computed(() => editForm.title.toUpperCase()),  // computed — ne pas mettre ici
});
```

---

## Traductions Vue (`locales/`)

Les fichiers `assets/client/locales/{fr,en}.js` contiennent les labels
utilisés **uniquement côté Vue** (boutons, labels de permissions dans l'UI) :

```js
// assets/client/locales/fr.js
export default {
  tracking: {
    projects: {
      title: 'Projets',
      create: 'Nouveau projet',
    },
  },
  backend: {
    permissions: {
      names: {
        tracking: {
          projects: {
            manage: 'Gérer les projets',
            delete: 'Supprimer les projets',
          },
        },
      },
    },
  },
};
```

> **Important** : pour chaque `NavPermission('tracking.projects.manage')` déclaré
> dans `TrackingModule`, ajouter la clé `backend.permissions.names.tracking.projects.manage`
> en FR **et** EN dans les locales Vue. Sans ça, la permission s'affiche avec
> sa clé brute dans l'UI de gestion des droits.

---

## Overrides de composants Aurora

Pour remplacer un composant Aurora existant, créer un fichier sous
`assets/client/Overrides/` en miroir du chemin Aurora :

```
# Composant Aurora
vendor/axelraboit/aurora/assets/Core/backend/agencies/AgenciesApp.vue
                                 ↓
# Override client
assets/client/Overrides/backend/agencies/AgenciesApp.vue
```

Le chemin `vue_component` reste identique — Aurora choisit automatiquement
le composant client s'il existe.

---

## Lancer le dev serveur

```bash
make dev     # Vite en mode watch — rechargement à chaud
make build   # Build de production
```

En développement, Vite est lancé automatiquement par `make start`.
