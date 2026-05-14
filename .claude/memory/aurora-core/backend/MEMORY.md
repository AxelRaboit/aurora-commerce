# Backend — PHP / Symfony / Doctrine

## Conventions métier
- [convention_timestampable.md](convention_timestampable.md) — `Aurora\Core\Timestampable\` (jamais KNP), `#[ORM\HasLifecycleCallbacks]` obligatoire, pas de `updateTimestamps()`
- [convention_extensibility.md](convention_extensibility.md) — résumé exécutif des 5 couches du pattern Sylius
- [convention_naming.md](convention_naming.md) — naming des variables, dossiers, fichiers
- [convention_dto_factory.md](convention_dto_factory.md) — pattern Input + InputFactory + AsAlias
- [convention_manager_hooks.md](convention_manager_hooks.md) — les 3 familles de hooks (createX, applyInput, audit*)
- [convention_audit_payload.md](convention_audit_payload.md) — règle d'extensibilité des audit logs
- [convention_doctrine_order_enum.md](convention_doctrine_order_enum.md) — `Order::Ascending->value` / `Order::Descending->value`, pas `'ASC'`/`'DESC'`
- [convention_interface_over_concrete.md](convention_interface_over_concrete.md) — type-hint l'Interface (jamais la Concrete)
- [convention_http_responses.md](convention_http_responses.md) — `jsonSuccess/jsonFailure/jsonInvalidInput` + `HttpStatusEnum`, jamais `$this->json([...], 503)` brut
- [convention_tmp_files_scheduler.md](convention_tmp_files_scheduler.md) — fichiers tmp : préfixe `aurora_<module>_<role>_` + `finally` + `CleanTempFilesHandler::TMP_PREFIXES`
- [convention_repository_eager_loading.md](convention_repository_eager_loading.md) — méthodes repo anti-N+1 : `findAllForIndex`, `findAllWith*`, `hydrate*Collections`

## Permissions & Navigation backend
- [convention_privilege_translations.md](convention_privilege_translations.md) — pour chaque `NavPermission('x.y.z')` ajouter `backend.permissions.names.x.y.z` en FR + EN
- [convention_privilege_gating.md](convention_privilege_gating.md) — gate à 2 endroits : `#[IsGranted]` serveur **et** `v-if="can(...)"` Vue
- [convention_privilege_granularity.md](convention_privilege_granularity.md) — `view/create/edit/delete`, jamais un `manage` fourre-tout
- [convention_navpermission_group.md](convention_navpermission_group.md) — `NavPermission(..., group: 'platform')` pour surfacer une perm sous une autre section
- [convention_breadcrumb_section.md](convention_breadcrumb_section.md) — premier fil = `backend.nav.sections.<moduleId>|trans`

## Structure PHP
- [structure_module_layout.md](structure_module_layout.md) — arborescence `src/Core/<Feature>/` ou `src/Module/<Module>/<Feature>/`
- [structure_entity.md](structure_entity.md) — Interface + Abstract + concrete, table naming, sequences `seq_core_*`
- [structure_controller.md](structure_controller.md) — Controllers Backend/Frontend, routes, type-hints, traits, conventions frontend controller
- [structure_manager_vs_service.md](structure_manager_vs_service.md) — quand `Manager/` vs `Service/`
- [structure_repository.md](structure_repository.md) — `ResolveTargetEntityRepository` pattern, finders
- [structure_view_builder.md](structure_view_builder.md) — `<Plural>ViewBuilder` admin + variante Frontend (`View/Frontend/`, `baseView()`, `pageData()`)

## Pièges
- [pitfall_readonly_class.md](pitfall_readonly_class.md) — `readonly class` ≠ `class { public readonly … }`
- [pitfall_resolve_target_entities.md](pitfall_resolve_target_entities.md) — Doctrine résout les relations, pas `new`
- [pitfall_type_hint_interface.md](pitfall_type_hint_interface.md) — décoration impose le type-hint interface
- [pitfall_service_entity_repository.md](pitfall_service_entity_repository.md) — `ServiceEntityRepository` hardcode la classe → `ResolveTargetEntityRepository`
- [pitfall_bundle_get_path.md](pitfall_bundle_get_path.md) — `AbstractBundle::getPath()` retourne la racine projet → nest infini `assets:install`
- [pitfall_module_translations_two_registrations.md](pitfall_module_translations_two_registrations.md) — seul `resolve_target_entities` est manuel, tout le reste est auto-découvert
- [pitfall_sequence_generator_naming.md](pitfall_sequence_generator_naming.md) — `app_seq_*` = séquences métier, `seq_core_*_id` = PKs Doctrine
- [pitfall_yaml_duplicate_keys.md](pitfall_yaml_duplicate_keys.md) — clé YAML dupliquée : silencieuse PHP, fatale pour DumpJsTranslations + build Vue
- [pitfall_route_gate_priority.md](pitfall_route_gate_priority.md) — `*RouteGateSubscriber` priorité < 8 obligatoire, convention : priorité 0
