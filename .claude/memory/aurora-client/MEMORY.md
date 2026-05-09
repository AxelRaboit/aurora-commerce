# Aurora-client — index mémoire (distribuée via aurora-core)

Ce dossier vit dans aurora-core et est **distribué via composer** quand un
client met à jour `axelraboit/aurora`. Aurora-client charge ces mémoires
depuis `vendor/axelraboit/aurora/.claude/memory/aurora-client/`.

**Avantage** : pas de duplication. Une mise à jour de la convention
d'aurora-core met automatiquement à jour les patterns d'extension côté
client.

## Index — patterns d'extension (5 couches Sylius)

- [pattern_extend_entity.md](pattern_extend_entity.md) — étendre `<Name>`
  avec un champ custom + `resolve_target_entities`
- [pattern_extend_dto.md](pattern_extend_dto.md) — étendre `<Name>Input` +
  décorer `<Name>InputFactory`
- [pattern_extend_manager.md](pattern_extend_manager.md) — override des
  hooks `protected` (createX, applyInput, auditPayload)
- [pattern_extend_serializer.md](pattern_extend_serializer.md) — override
  `serialize()` avec spread `parent`
- [pattern_extend_vue.md](pattern_extend_vue.md) — passer `extraFields` +
  utiliser les slots scoped
- [pattern_extend_repository.md](pattern_extend_repository.md) — étendre
  finders custom (limite assumée : pas d'interface aurora-core)
- [pattern_override_twig.md](pattern_override_twig.md) — namespace prepend
  automatique du bundle

## Index — patterns avancés

- [pattern_add_custom_permissions.md](pattern_add_custom_permissions.md) —
  ajouter des permissions custom via `ModuleInterface` (auto-tag, groupage par
  module id, traduction obligatoire)

## Index — pièges côté client

- [pitfall_create_hook_required.md](pitfall_create_hook_required.md) —
  override `create<X>()` sinon Doctrine perd les champs custom
- [pitfall_call_parent_apply_input.md](pitfall_call_parent_apply_input.md)
  — toujours `parent::applyInput()` avant d'ajouter ses propres setters

## Index — checklist d'extension complète d'une entité

- [checklist_extend_full_entity.md](checklist_extend_full_entity.md) — pas
  à pas pour étendre une entité de bout en bout (entité + DTO + Manager +
  Serializer + Vue + migration)

## Règles d'usage

- Ne pas dupliquer les conventions d'aurora-core (lire [convention_extensibility.md](../convention_extensibility.md)
  d'abord).
- Si un nouveau pattern client émerge (cas non couvert par les fichiers
  ci-dessus), créer un fichier ici + ajouter à l'index. Le pattern sera
  alors distribué à tous les autres clients via composer update.
