# Vue / JS — Transversal (admin + public)

- [convention_vue_directives.md](convention_vue_directives.md) — `v-on:click` (jamais `@click`), `:` shorthand OK pour `v-bind`
- [convention_js_privacy.md](convention_js_privacy.md) — `#field` dans les classes (jamais `_field`), variable module-level non exportée
- [convention_i18n_source_files.md](convention_i18n_source_files.md) — éditer les YAML sources, jamais le JSON généré. `make i18n` régénère
- [convention_i18n_key_casing.md](convention_i18n_key_casing.md) — `snake_case` si clé construite par le code, `camelCase` si nommée manuellement. Mixte intentionnel
- [convention_no_raw_fetch.md](convention_no_raw_fetch.md) — jamais `await fetch()` brut → `useRequest` (admin) ou `useFrontendRequest` (public)
- [structure_assets_vue.md](structure_assets_vue.md) — composants Vue, composables, naming, `frontend/components/` + `frontend/composables/`, anti-patterns
- [convention_assets_subfolder_layout.md](convention_assets_subfolder_layout.md) — compartimentage feature-subfolder dans `assets/Module/<M>/backend/`
