# Aurora-core — index mémoire projet (core)

Ce fichier est l'index des mémoires spécifiques à **aurora-core**. Il est
référencé par `CLAUDE.md` à la racine. Les futures sessions peuvent enrichir
cette base — toute nouvelle convention/pattern/piège rencontré doit être ajouté
ici sous forme d'un fichier dédié + une ligne dans cet index.

**Principe** : ne pas dupliquer le contenu des docs `docs/aurora-core/dev/*.md` — y
pointer. Capturer ici les **règles**, **décisions**, **pièges** et
**heuristiques** transversaux qui ne vivent pas naturellement dans les docs.

## Index

### Conventions PHP / Symfony / Doctrine
- [convention_extensibility.md](convention_extensibility.md) — résumé exécutif des 5 couches du pattern Sylius
- [convention_naming.md](convention_naming.md) — naming des variables, dossiers, fichiers
- [convention_dto_factory.md](convention_dto_factory.md) — pattern Input + InputFactory + AsAlias
- [convention_manager_hooks.md](convention_manager_hooks.md) — les 3 familles de hooks (createX, applyInput, audit*)
- [convention_audit_payload.md](convention_audit_payload.md) — règle d'extensibilité des audit logs
- [convention_doctrine_order_enum.md](convention_doctrine_order_enum.md) — utiliser `Order::Ascending->value` / `Order::Descending->value`, pas `'ASC'`/`'DESC'`
- [convention_interface_over_concrete.md](convention_interface_over_concrete.md) — type-hint l'Interface (jamais la Concrete) dans repos/managers/serializers/setters/collections/array_map
- [convention_privilege_translations.md](convention_privilege_translations.md) — pour chaque `NavPermission('x.y.z')` ajouter `backend.permissions.names.x.y.z` (format nested) en FR + EN dans le YAML du module
- [convention_privilege_gating.md](convention_privilege_gating.md) — gate les actions à 2 endroits : `#[IsGranted]` côté serveur (autorité) **et** `v-if="can(...)"` côté Vue (UX). Jamais l'un sans l'autre
- [convention_privilege_granularity.md](convention_privilege_granularity.md) — toujours décomposer en `view/create/edit/delete` plutôt qu'un `manage` fourre-tout. Permet des profils de droits fins pour `ROLE_USER`
- [convention_breadcrumb_section.md](convention_breadcrumb_section.md) — premier fil de breadcrumb = `backend.nav.sections.<moduleId>|trans` (appliqué sur 31 templates)

### Conventions Vue / JS / Frontend
- [convention_vue_form_validation.md](convention_vue_form_validation.md) — `useForm` + `required()` + `:error` ; `useI18n()` dans le composable ; reset loading sur tous les chemins
- [convention_form_components.md](convention_form_components.md) — toujours `App*` au lieu de `<button>`/`<input>`/`<select>` brut, placeholders obligatoires, `AppDatePicker` (jamais `type="date"` natif)
- [convention_vue_directives.md](convention_vue_directives.md) — toujours `v-on:click` (jamais `@click`), `:` shorthand OK pour `v-bind`
- [convention_modal_and_confirmation.md](convention_modal_and_confirmation.md) — `AppModal` API (`:show + v-on:close`) + confirmation suppression via modale, jamais `confirm()` natif
- [convention_js_privacy.md](convention_js_privacy.md) — privacy JS : `#field` dans les classes (jamais `_field`), variable module-level non exportée pour les composables
- [convention_i18n_source_files.md](convention_i18n_source_files.md) — éditer les **YAML sources** (`src/.../translations/`), jamais le JSON généré. `make i18n` régénère, `npm run build` consomme
- [convention_http_responses.md](convention_http_responses.md) — toujours `jsonSuccess/jsonFailure/jsonInvalidInput` + `HttpStatusEnum` — jamais `$this->json([...], 503)` brut ni code HTTP en dur
- [convention_tmp_files_scheduler.md](convention_tmp_files_scheduler.md) — fichiers tmp : préfixe `aurora_<module>_<role>_` + `finally` + déclaration dans `CleanTempFilesHandler::TMP_PREFIXES` ; fichiers techniques → jamais MediaManager ; tâches récurrentes → Symfony Scheduler
- [convention_i18n_key_casing.md](convention_i18n_key_casing.md) — `snake_case` si la clé est construite par le code (enum suffix, nav id), `camelCase` si nommée manuellement pour l'UI. Mixte intentionnel, ne pas uniformiser

### Structure du projet (où va quoi)
- [structure_module_layout.md](structure_module_layout.md) — arborescence d'un dossier `src/Core/<Feature>/` ou `src/Module/<Module>/<Feature>/`
- [structure_entity.md](structure_entity.md) — Interface + Abstract + concrete, table naming, sequences `seq_core_*`
- [structure_controller.md](structure_controller.md) — Controllers Backend/Front, routes, type-hints, traits utiles
- [structure_manager_vs_service.md](structure_manager_vs_service.md) — quand mettre dans `Manager/` vs `Service/`
- [structure_repository.md](structure_repository.md) — `ResolveTargetEntityRepository` pattern, finders
- [structure_view_builder.md](structure_view_builder.md) — `<Plural>ViewBuilder` pour les payloads Twig admin
- [structure_templates.md](structure_templates.md) — namespaces Twig, override automatique, conventions de naming
- [structure_assets_vue.md](structure_assets_vue.md) — composants Vue, composables, naming, patterns extension, anti-patterns

### Décisions architecturales
- [architecture_module_parameter_enum.md](architecture_module_parameter_enum.md) — `ModuleParameterEnum` séparé d'`ApplicationParameterEnum` : 13 top-level + 24 sous-modules, cascade graph, consommateurs, convention clés sans `_enabled`
- [pattern_domain_events_cross_module.md](pattern_domain_events_cross_module.md) — Core dispatche des events mutables, les modules écoutent. Jamais d'import `Core → Module`. Exemple : `UserAgencyServiceUpdatingEvent` + `HrEmployeeSyncListener`
- [pattern_user_scoped_module_access.md](pattern_user_scoped_module_access.md) — `ModuleAccessChecker` central : global setting + per-user `disabled_modules` JSON + cascade. Tous les `*Context` y routent. Privilege `core.users.modules.manage` (dev+admin)
- [decision_4_hard_rules.md](decision_4_hard_rules.md) — les 4 règles dures issues de l'audit
- [decision_variant_user_style.md](decision_variant_user_style.md) — critères de la variante "Manager à hooks multiples"
- [decision_repository_no_interface.md](decision_repository_no_interface.md) — pourquoi pas d'interface `<Name>RepositoryInterface`

### Pièges PHP / Symfony / Doctrine
- [pitfall_readonly_class.md](pitfall_readonly_class.md) — `readonly class` ≠ `class { public readonly … }`
- [pitfall_resolve_target_entities.md](pitfall_resolve_target_entities.md) — Doctrine résout les relations, pas `new`
- [pitfall_type_hint_interface.md](pitfall_type_hint_interface.md) — décoration impose le type-hint interface
- [pitfall_service_entity_repository.md](pitfall_service_entity_repository.md) — `ServiceEntityRepository` hardcode la classe → utiliser `ResolveTargetEntityRepository`
- [pitfall_bundle_get_path.md](pitfall_bundle_get_path.md) — `AbstractBundle::getPath()` par défaut retourne la racine projet, ce qui fait copier récursivement `public/` dans `public/bundles/aurora/` (7.9 GB de nest infini lors d'un `assets:install`)
- [pitfall_module_translations_two_registrations.md](pitfall_module_translations_two_registrations.md) — nouveau module : seul `resolve_target_entities` dans `AuroraBundle.php` est manuel. Mappings Doctrine, Twig namespaces, translator paths et DumpJsTranslations sont tous auto-découverts par glob.
- [pitfall_sequence_generator_naming.md](pitfall_sequence_generator_naming.md) — `app_seq_*` = séquences métier (SequenceGenerator), `seq_core_*_id` = PKs Doctrine. Ne pas confondre. `schema_filter` dans doctrine.yaml exclut `app_seq_*` des diffs
- [pitfall_yaml_duplicate_keys.md](pitfall_yaml_duplicate_keys.md) — clé YAML dupliquée (scalar + mapping même niveau) : silencieuse en PHP, fatale pour `DumpJsTranslationsCommand` et le build Vue

### Process / méthode
- [process_make_ft_before_commit.md](process_make_ft_before_commit.md) — **toujours** `make ft` (fix + test) avant chaque commit, résoudre tous les problèmes
- [process_audit_before_generalize.md](process_audit_before_generalize.md) — auditer avant de généraliser une convention sur N entités
- [process_atomic_commits.md](process_atomic_commits.md) — un commit par entité lors des rollouts massifs

### Préférences utilisateur
- [pref_no_co_authored.md](pref_no_co_authored.md) — pas de `Co-Authored-By` Claude dans les commits
- [pref_commit_language.md](pref_commit_language.md) — messages de commit toujours en anglais
- [pref_french_dialogue.md](pref_french_dialogue.md) — l'utilisateur parle français, Claude répond en français

### Contexte projet
- [project_aurora_client_role.md](project_aurora_client_role.md) — aurora-client est un projet démo + template de départ pour de nouveaux projets
- [project_vault_module.md](project_vault_module.md) — Module Vault E2E ajouté 2026-05-09 : particularités ownership user-scopé, crypto PBKDF2+AES-GCM, permission unique `vault.use`

## Règles d'usage

- **Lecture** : toute mémoire potentiellement pertinente doit être lue avant
  d'agir. Ne pas se reposer uniquement sur le résumé ; ouvrir le fichier
  source si le sujet est touché.
- **Écriture** : si une nouvelle convention émerge ou un piège est découvert,
  créer un fichier `<type>_<topic>.md` ici + ajouter une ligne dans l'index.
  Format : `## Règle` puis `## Pourquoi` puis `## Comment l'appliquer`.
- **Mise à jour** : si une mémoire devient obsolète (refacto, changement de
  décision), corriger ou supprimer — ne pas accumuler de l'obsolète.
