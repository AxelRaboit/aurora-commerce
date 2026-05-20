# TODO — Commande CLI `aurora:make:entity`

## Contexte

`aurora:make:module` scaffold le squelette d'un nouveau module (PHP + Vue +
traductions + auto-patches client). Le pendant manquant : une commande qui
scaffold le squelette d'une **entité CRUD** à l'intérieur d'un module
existant — pour éliminer ~700 lignes de boilerplate identique d'une entité
à l'autre (Entity triplet + DTO quartet + Manager pair + Serializer pair +
Repository + Controller + ViewBuilder + Vue + composable).

La skill Claude Code `/add-entity` couvre déjà ce scope mais via LLM ;
une commande CLI ferait pareil sans tour de génération, et serait
scriptable.

## Direction d'implémentation

### Phase 1 — Squelette PHP (cible initiale)

```bash
php bin/console aurora:make:entity Billing Refund
#                                  └─┬───┘ └──┬──┘
#                                  module    name
```

Génère 13 fichiers PHP dans `src/Module/Billing/Refund/` :

| Couche | Fichiers |
|---|---|
| 1 — Entity | `Entity/<Name>Interface.php` + `Entity/Abstract<Name>.php` + `Entity/<Name>.php` (single field `name: string(150)`, séquence `seq_core_<snake>_id`) |
| 2 — DTO | `Dto/<Name>InputInterface.php` + `Dto/<Name>Input.php` + `Dto/<Name>InputFactoryInterface.php` + `Dto/<Name>InputFactory.php` (avec `#[AsAlias]`) |
| 3 — Manager | `Manager/<Name>ManagerInterface.php` + `Manager/<Name>Manager.php` (avec hooks `protected createX()`, `applyInput()`, `auditCreated/Updated/Deleted`, `auditPayload`) |
| 4 — Serializer | `Serializer/<Name>SerializerInterface.php` + `Serializer/<Name>Serializer.php` (avec `#[AsAlias]`) |
| Repository | `Repository/<Name>Repository.php` étend `ResolveTargetEntityRepository` |
| Controller | `Controller/Backend/<Plural>Controller.php` (5 actions : `index`, `list`, `create`, `update`, `delete`) |
| View | `View/<Plural>ViewBuilder.php` skeleton |

Plus 2 fichiers édités auto :
- `src/AuroraBundle.php` — ajouter `<Name>Interface::class => <Name>::class` dans `resolve_target_entities`
- (côté client) `config/packages/doctrine.yaml` — pareil mais auto-mapping

### Phase 2 — Vue + composable

Ajout des 2 fichiers Vue :
- `src/Module/<Module>/<Feature>/assets/backend/<plural>/<Plural>App.vue` (avec slots scoped `extra-headers` / `extra-cells` / `extra-form-fields` + prop `extraFields`)
- `src/Module/<Module>/<Feature>/assets/backend/<plural>/composables/use<Plural>Form.js`

Plus :
- Append des clés `backend.<plural>.*` dans `messages.{fr,en}.yaml` du module

### Phase 3 — Migration (optionnel)

- Auto-call `php bin/console doctrine:migrations:diff` après scaffold
- Ou juste imprimer le hint `make migration && make migrate` dans next-steps

## Inputs CLI

Convention argument : `aurora:make:entity <Module> <Name>` où :
- `<Module>` = module **existant** (refuse si `src/Module/<Module>/` absent) avec optionnellement un feature folder (`Billing/Refund`)
- `<Name>` = PascalCase singulier (`Refund`, `Customer`, `BlogPost`)

Optionnel :
- `--plural=<Plural>` pour les pluriels irréguliers (`Taxonomy` → `--plural=Taxonomies`). Par défaut on suffixe `s`.
- `--field=name:string(150)` pour pré-déclarer 1+ champs au scaffold time (par défaut juste `name`).

Détection contexte (core vs client) via `composer.json` comme dans `aurora:make:module`.

## Templates à créer

Tous sous `src/Core/Module/Command/templates/entity/` :
- `Entity.Interface.php.tpl`
- `Entity.Abstract.php.tpl`
- `Entity.Concrete.php.tpl`
- `Dto.InputInterface.php.tpl`
- `Dto.Input.php.tpl`
- `Dto.InputFactoryInterface.php.tpl`
- `Dto.InputFactory.php.tpl`
- `Manager.Interface.php.tpl`
- `Manager.php.tpl`
- `Serializer.Interface.php.tpl`
- `Serializer.php.tpl`
- `Repository.php.tpl`
- `Controller.php.tpl`
- `ViewBuilder.php.tpl`

Phase 2 :
- `App.vue.tpl`
- `composable.js.tpl`
- `messages-append.yaml.tpl` (snippet à appendre aux YAML existants)

## Pointeurs code

- **Référence canonique** : `src/Module/Platform/Agency/*` (entité pilote complète de la convention 5 couches)
- **Convention doc** : [`docs/aurora-core/dev/entity_extensibility_convention.md`](../dev/entity_extensibility_convention.md)
- **Skill Claude équivalente** : `.claude/skills/add-entity/SKILL.md` (suivre la même logique côté CLI)
- **Maker module existant** : `src/Core/Module/Command/MakeModuleCommand.php` (modèle de structure)

## Étapes

1. Définir la liste exhaustive des placeholders dans les templates
   (`{{MODULE}}`, `{{MODULE_NS}}`, `{{FEATURE}}`, `{{NAME}}`, `{{PLURAL}}`,
   `{{SNAKE}}`, `{{KEBAB}}`, `{{CAMEL}}`, `{{TABLE}}`, etc.)
2. Créer les 14 fichiers `.tpl` Phase 1
3. Implémenter `MakeEntityCommand` (extends Command, suit le pattern de `MakeModuleCommand`)
4. Auto-edit `AuroraBundle.php` / `doctrine.yaml` pour `resolve_target_entities`
5. Smoke test : scaffold une entité, vérifier compile + tests
6. Update doc `entity_extensibility_convention.md` § "Comment scaffolder une nouvelle entité"
7. Update skill `/add-entity` SKILL.md pour mentionner la commande CLI
8. Phase 2 : Vue + composable (raisonnement séparé sur slots/extraFields)
9. Phase 3 : migration auto (optionnel)

## Décisions à prendre avant de coder

- **Plural** : auto-derive (suffix `s`) ou prompt obligatoire ? Anglais a plein
  d'irréguliers (`Taxonomy → Taxonomies`, `Person → People`).
- **Field initial** : juste `name` ou supporter `--field` répétable (multi-champs au scaffold) ?
- **Migration auto** : risk de casse si user pas en dev mode. Probable : juste hint.
- **Client vs core** : un seul `MakeEntityCommand` qui détecte le contexte, OU deux commandes distinctes (`aurora:make:entity` core, `aurora:make:entity` client-via-vendor) ? Le maker module fait one-command détection, suivre le même pattern.

## Estimation

~3-4h pour Phase 1 (templates + command + tests + doc).
Phase 2 ajoute ~2h.
Phase 3 ajoute ~30min.
