# Architecture & Décisions

- [architecture_module_parameter_enum.md](architecture_module_parameter_enum.md) — `ModuleParameterEnum` séparé d'`ApplicationParameterEnum` : cascade graph, convention clés sans `_enabled`
- [pattern_domain_events_cross_module.md](pattern_domain_events_cross_module.md) — Core dispatche des events mutables, les modules écoutent. Jamais d'import `Core → Module`
- [pattern_user_scoped_module_access.md](pattern_user_scoped_module_access.md) — `ModuleAccessChecker` : global setting + per-user `disabled_modules` JSON + cascade
- [pattern_user_sidebar_preferences.md](pattern_user_sidebar_preferences.md) — couche user-controlled `hidden_nav_sections` + `hidden_nav_items` ; tokens stables NavSection.id / NavItem.route
- [pattern_frontend_toggle.md](pattern_frontend_toggle.md) — chaque front a son toggle dédié. `FrontendRouteGateSubscriber` 404 les routes désactivées
- [pattern_frontend_descriptor.md](pattern_frontend_descriptor.md) — convention `<Module>FrontendDescriptor.php` à la racine du module pour tout `*Frontend` toggle (symétrie Editorial/Ecommerce/Photo/Ged)
- [decision_4_hard_rules.md](decision_4_hard_rules.md) — les 4 règles dures issues de l'audit
- [decision_variant_user_style.md](decision_variant_user_style.md) — critères de la variante "Manager à hooks multiples"
- [decision_repository_no_interface.md](decision_repository_no_interface.md) — pourquoi pas d'interface `<Name>RepositoryInterface`
- [decision_marketing_taxonomies_on_listing.md](decision_marketing_taxonomies_on_listing.md) — taxonomies marketing (Category/Tag) sur `Listing` jamais sur `Product` ; URL canonique produit découplée des filtres
- [pattern_app_config_bootstrap.md](pattern_app_config_bootstrap.md) — exposer un `ApplicationParameter` aux composants Vue via `window.__auroraConfig.<key>` (extension Twig dédiée + fallback hardcodé)
- [pattern_single_locale_mode.md](pattern_single_locale_mode.md) — toggle `single_locale_mode` réversible : filtre l'UI/écritures via `LocaleContext`, préserve les `XxxTranslation` dormantes
- [decision_locale_added_in_core.md](decision_locale_added_in_core.md) — toute nouvelle locale (es, de…) s'ajoute dans `LocaleEnum` côté core, jamais côté client (capitalisation + cohérence cross-écosystème)
