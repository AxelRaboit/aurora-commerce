# Vue Frontend — Vue / Twig (Site public)

- [convention_frontend_rendering.md](convention_frontend_rendering.md) — **règle de base** : Twig shell + Vue par défaut, Twig SSR complet uniquement pour les pages indexées (SEO)
- [convention_frontend_search.md](convention_frontend_search.md) — client-side (`v-model` + computed) vs server-side (`AppSearchInput @search` + endpoint JSON) : critère volume + patterns complets
- [structure_templates.md](structure_templates.md) — `Frontend/themes/default/{module}/`, ThemeResolver, override par thème, `showFrontMenus`
