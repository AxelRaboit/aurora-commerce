# Vue / JS — Transversal (admin + public)

- [convention_testing.md](convention_testing.md) — co-location obligatoire `.test.js` à côté de la source ; patterns composant/composable/util ; stubs courants ; ce qu'on skip
- [convention_form_components.md](convention_form_components.md) — toujours `App*` (AppButton, AppInput, AppRange, AppPagination…) jamais HTML brut — **admin ET frontend public**
- [convention_vue_directives.md](convention_vue_directives.md) — `v-on:click` (jamais `@click`), `:` shorthand OK pour `v-bind`
- [convention_js_privacy.md](convention_js_privacy.md) — `#field` dans les classes (jamais `_field`), variable module-level non exportée
- [convention_i18n_source_files.md](convention_i18n_source_files.md) — éditer les YAML sources, jamais le JSON généré. `make i18n` régénère
- [convention_i18n_key_casing.md](convention_i18n_key_casing.md) — `snake_case` si clé construite par le code, `camelCase` si nommée manuellement. Mixte intentionnel
- [convention_no_raw_fetch.md](convention_no_raw_fetch.md) — jamais `await fetch()` brut → `useRequest` (admin) ou `useFrontendRequest` (public)
- [structure_assets_vue.md](structure_assets_vue.md) — composants Vue, composables, naming, `frontend/components/` + `frontend/composables/`, anti-patterns
- [convention_assets_subfolder_layout.md](convention_assets_subfolder_layout.md) — compartimentage feature-subfolder dans `assets/Module/<M>/backend/`
- [composable_hierarchical_tree.md](composable_hierarchical_tree.md) — `@/shared/composables/tree/useHierarchicalTree.js` (`buildTree`, `flattenTreeForReorder`, …) — ne pas dupliquer
- [composable_url_pagination.md](composable_url_pagination.md) — `useUrlPagination` pour la pagination full-reload (`?page=N`) — pas pour l'AJAX
- [composable_client_filtered_list.md](composable_client_filtered_list.md) — `useClientFilteredList` pour les listes admin courtes (items + searchInput + filteredItems + reload), pendant client-side de `useListPage`
- [utility_pick_translation.md](utility_pick_translation.md) — `pickTranslation` / `translatedField` avec fallback locale → en → première dispo
- [convention_twig_locale_extension.md](convention_twig_locale_extension.md) — `locale_flag()` / `locale_name()` Twig depuis `LocaleExtension.php`
