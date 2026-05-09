# Assets Vue / Composables — convention de structure

## Règle

```
assets/
├── Core/
│   ├── backend/
│   │   ├── <plural>/                       ← un dossier par entité (au pluriel)
│   │   │   ├── <Plural>App.vue              ← composant principal monté par Stimulus
│   │   │   ├── <Detail>App.vue              ← optional, page détail
│   │   │   └── composables/
│   │   │       ├── use<Plural>Form.js       ← create+edit unifié (option extraFields)
│   │   │       ├── use<Plural>Delete.js
│   │   │       ├── use<Plural>List.js
│   │   │       └── … (autres composables spécifiques)
│   │   └── …
│   ├── frontend/                           ← composants frontend public
│   └── utils/                              ← helpers JS partagés Core
├── Module/
│   └── <Module>/
│       └── backend/<plural>/                ← idem Core mais pour les modules
└── shared/
    ├── components/                         ← AppButton, AppInput, AppModal, etc.
    │   ├── action/
    │   ├── feedback/
    │   ├── form/
    │   ├── nav/
    │   └── overlay/
    ├── composables/
    │   ├── api/                            ← useApiRequest, etc.
    │   ├── form/                           ← useForm
    │   ├── format/                         ← useDateFormat
    │   └── list/                           ← useListPage, useUrlSearchSync
    └── utils/                              ← buildPath, httpMethod, validation, …
```

## Composants Vue

### Naming
- **PascalCase** : `AgenciesApp.vue`, `PostEditor.vue`, `MediaCropperModal.vue`.
- **App suffixe** : composant top-level monté via Stimulus
  (`AgenciesApp.vue`, `MediaApp.vue`, `UsersApp.vue`).
- **Modal/Overlay suffixe** : composant secondaire ouvert par l'App
  (`MediaCropperModal.vue`, `RevisionsOverlay.vue`).
- **Panel suffixe** : section d'un editor (`PostSeoPanel.vue`,
  `PostFeaturedImagePanel.vue`).
- **Tab suffixe** : onglet dans un AppTabs (`AdminPermissionsTab.vue`).

### Convention extension (rappel)
Chaque `<Plural>App.vue` (composant top-level d'admin CRUD) expose :
- **Prop `extraFields`** (`{ type: Object, default: () => ({}) }`)
- **3 slots scoped** : `extra-headers`, `extra-cells`, `extra-form-fields`

Cf [`convention_extensibility.md`](convention_extensibility.md) couche 5
et [`client/pattern_extend_vue.md`](client/pattern_extend_vue.md).

## Composables JS

### Localisation
`assets/<...>/<plural>/composables/use<Plural><Action>.js`.

### Naming
- `use<Plural>Form.js` : composable unifié create+edit (le plus courant).
- `use<Plural>Delete.js` : confirmation + delete.
- `use<Plural>List.js` : liste réactive (souvent juste un wrapper).
- `use<Plural>Filters.js` : filtres de recherche.
- `use<Plural>Kanban.js`, `use<Plural>Reorder.js` : actions spécifiques.

### Pattern composable Form unifié

```js
// useAgenciesForm.js
import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";

/**
 * @typedef {Object} ExtraField
 * @property {*} default - Initial/reset value for this field.
 * @property {(agency: object) => *} fromEntity - Reads field value from existing agency.
 */

export function useAgenciesForm(agencyList, createPath, updatePath, options = {}) {
    const { t } = useI18n();
    const { request } = useApiRequest();
    const extraFields = options.extraFields ?? {};

    const editModal = reactive({ open: false, agency: null, errors: {}, saving: false });
    const editForm = reactive({
        name: "",
        ...Object.fromEntries(
            Object.entries(extraFields).map(([key, def]) => [key, def.default]),
        ),
    });

    function openCreate() { /* reset form + open modal */ }
    function openEdit(agency) { /* hydrate form + open modal */ }
    async function submitEdit() { /* POST to create/update + handle response */ }

    return { editModal, editForm, openCreate, openEdit, submitEdit };
}
```

### Variantes de composable
- **Composable form unifié** (Agency, Service, Theme[create-only],
  Company, Contact, Deal, Order admin, Form, Comment, Document,
  DocumentCategory, Listing, Product, Gallery, Menu, Media, …) — la
  norme.
- **Composable séparé** create + edit (User invite/edit, Theme
  create/edit) : 2 composables distincts. Cf
  [`decision_variant_user_style.md`](decision_variant_user_style.md).
- **Editor full-page** (Post) : pas de composable form séparé — la logique
  est dans le composant `PostEditor.vue` directement (trop de complexité
  pour un composable).

## Stimulus controllers

Le projet utilise Stimulus comme bridge Twig → Vue. Controllers dans
`assets/controllers/` (un controller par usage : `vue-mount`,
`form-validation`, `notification-bell`, etc.).

Pattern principal : `data-controller="vue-mount" data-vue-mount-component-value="…"`
pour mount un composant Vue depuis Twig.

## Conventions de code Vue

### Reactive vs Ref
- `ref()` pour les valeurs scalaires ou tableaux entiers.
- `reactive()` pour les objets de form (`editForm`, `editModal`).
- ❌ **Pas de ref imbriqué dans reactive** : sérialisation imprévisible.
  Utiliser des ref/reactive séparés.

### Imports avec alias
- `@/shared/...` pour les helpers shared.
- `@core/backend/...` pour les composants Core admin.
- `@<module>/backend/...` pour les composants module admin.

Les alias sont configurés dans `vite.config.js` côté Core.

### Style

- `<script setup>` Composition API (Vue 3).
- `defineProps({ ... })` pour les props.
- Pas de Options API (pas de `data() { return ... }`).

## Build

```bash
npm run dev    # Vite dev server
npm run build  # Production build
```

Côté client, le build est lancé via `pnpm` depuis le dossier aurora-core
en mode `AURORA_ENV` qui scanne aussi `assets/` du client.

Cf `aurora-core/Makefile` (target `build` / `dev`) pour les détails.

## Anti-patterns

- ❌ Composant top-level sans `extraFields` prop ni slots scoped (impossible
  à étendre côté client).
- ❌ Composables qui font de l'HTTP direct via `fetch()` au lieu d'utiliser
  `useApiRequest` (rate de gérer toast d'erreur, loading state, etc.).
- ❌ Logique métier dans le composant (calculs complexes, transitions de
  statut). Mettre dans un composable dédié.
- ❌ Composant naming inconsistant : `AgenciesApp` (plural) vs
  `PostEditor` (singular). Pour les admin CRUD top-level → toujours
  `<Plural>App`.
