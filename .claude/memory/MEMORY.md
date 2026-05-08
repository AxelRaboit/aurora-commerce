# Aurora-core — index mémoire projet

Ce fichier est l'index des mémoires projet aurora-core. Il est référencé
par `CLAUDE.md` à la racine. Les futures sessions peuvent enrichir cette
base — toute nouvelle convention/pattern/piège rencontré doit être ajouté
ici sous forme d'un fichier dédié + une ligne dans cet index.

**Principe** : ne pas dupliquer le contenu des docs `docs/dev/*.md` — y
pointer. Capturer ici les **règles**, **décisions**, **pièges** et
**heuristiques** transversaux qui ne vivent pas naturellement dans les docs.

**Note structurelle** : les mémoires destinées à `aurora-client` (patterns
d'extension côté consommateur) vivent dans le sous-dossier
[`client/`](client/MEMORY.md). Elles sont **distribuées via composer**
quand un client met à jour `axelraboit/aurora` — pas de duplication, pas
de désynchronisation possible. Aurora-client charge ces mémoires depuis
`vendor/axelraboit/aurora/.claude/memory/client/`.

## Index

### Conventions de code
- [convention_extensibility.md](convention_extensibility.md) — résumé exécutif des 5 couches du pattern Sylius
- [convention_naming.md](convention_naming.md) — naming des variables, dossiers, fichiers
- [convention_dto_factory.md](convention_dto_factory.md) — pattern Input + InputFactory + AsAlias
- [convention_manager_hooks.md](convention_manager_hooks.md) — les 3 familles de hooks (createX, applyInput, audit*)
- [convention_audit_payload.md](convention_audit_payload.md) — règle d'extensibilité des audit logs
- [convention_doctrine_order_enum.md](convention_doctrine_order_enum.md) — utiliser `Order::Ascending->value` / `Order::Descending->value`, pas `'ASC'`/`'DESC'`
- [convention_interface_over_concrete.md](convention_interface_over_concrete.md) — type-hint l'Interface (jamais la Concrete) dans repos/managers/serializers/setters/collections/array_map

### Structure du projet (où va quoi)
- [structure_module_layout.md](structure_module_layout.md) — arborescence d'un dossier `src/Core/<Feature>/` ou `src/Module/<Module>/<Feature>/`
- [structure_entity.md](structure_entity.md) — Interface + Abstract + concrete, table naming, sequences `seq_core_*`
- [structure_controller.md](structure_controller.md) — Controllers Backend/Front, routes, type-hints, traits utiles
- [structure_manager_vs_service.md](structure_manager_vs_service.md) — quand mettre dans `Manager/` vs `Service/`
- [structure_repository.md](structure_repository.md) — `ResolveTargetEntityRepository` pattern, finders
- [structure_view_builder.md](structure_view_builder.md) — `<Plural>ViewBuilder` pour les payloads Twig admin
- [structure_templates.md](structure_templates.md) — namespaces Twig, override automatique, conventions de naming
- [structure_assets_vue.md](structure_assets_vue.md) — composants Vue, composables, naming, patterns extension

### Décisions architecturales
- [decision_4_hard_rules.md](decision_4_hard_rules.md) — les 4 règles dures issues de l'audit
- [decision_variant_user_style.md](decision_variant_user_style.md) — critères de la variante "Manager à hooks multiples"
- [decision_repository_no_interface.md](decision_repository_no_interface.md) — pourquoi pas d'interface `<Name>RepositoryInterface`

### Pièges PHP / Symfony / Doctrine
- [pitfall_readonly_class.md](pitfall_readonly_class.md) — `readonly class` ≠ `class { public readonly … }`
- [pitfall_resolve_target_entities.md](pitfall_resolve_target_entities.md) — Doctrine résout les relations, pas `new`
- [pitfall_type_hint_interface.md](pitfall_type_hint_interface.md) — décoration impose le type-hint interface
- [pitfall_service_entity_repository.md](pitfall_service_entity_repository.md) — `ServiceEntityRepository` hardcode la classe → utiliser `ResolveTargetEntityRepository`

### Process / méthode
- [process_make_ft_before_commit.md](process_make_ft_before_commit.md) — **toujours** `make ft` (fix + test) avant chaque commit, résoudre tous les problèmes
- [process_audit_before_generalize.md](process_audit_before_generalize.md) — auditer avant de généraliser une convention sur N entités
- [process_atomic_commits.md](process_atomic_commits.md) — un commit par entité lors des rollouts massifs

### Préférences utilisateur
- [pref_no_co_authored.md](pref_no_co_authored.md) — pas de `Co-Authored-By` Claude dans les commits
- [pref_french_dialogue.md](pref_french_dialogue.md) — l'utilisateur parle français, Claude répond en français

## Règles d'usage

- **Lecture** : toute mémoire potentiellement pertinente doit être lue avant
  d'agir. Ne pas se reposer uniquement sur le résumé ; ouvrir le fichier
  source si le sujet est touché.
- **Écriture** : si une nouvelle convention émerge ou un piège est découvert,
  créer un fichier `<type>_<topic>.md` ici + ajouter une ligne dans l'index.
  Format : `## Règle` puis `## Pourquoi` puis `## Comment l'appliquer`.
- **Mise à jour** : si une mémoire devient obsolète (refacto, changement de
  décision), corriger ou supprimer — ne pas accumuler de l'obsolète.
