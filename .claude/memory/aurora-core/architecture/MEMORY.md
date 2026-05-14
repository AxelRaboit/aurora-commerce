# Architecture & Décisions

- [architecture_module_parameter_enum.md](architecture_module_parameter_enum.md) — `ModuleParameterEnum` séparé d'`ApplicationParameterEnum` : cascade graph, convention clés sans `_enabled`
- [pattern_domain_events_cross_module.md](pattern_domain_events_cross_module.md) — Core dispatche des events mutables, les modules écoutent. Jamais d'import `Core → Module`
- [pattern_user_scoped_module_access.md](pattern_user_scoped_module_access.md) — `ModuleAccessChecker` : global setting + per-user `disabled_modules` JSON + cascade
- [pattern_frontend_toggle.md](pattern_frontend_toggle.md) — chaque front a son toggle dédié. `FrontendRouteGateSubscriber` 404 les routes désactivées
- [decision_4_hard_rules.md](decision_4_hard_rules.md) — les 4 règles dures issues de l'audit
- [decision_variant_user_style.md](decision_variant_user_style.md) — critères de la variante "Manager à hooks multiples"
- [decision_repository_no_interface.md](decision_repository_no_interface.md) — pourquoi pas d'interface `<Name>RepositoryInterface`
- [decision_marketing_taxonomies_on_listing.md](decision_marketing_taxonomies_on_listing.md) — taxonomies marketing (Category/Tag) sur `Listing` jamais sur `Product` ; URL canonique produit découplée des filtres
