# Aurora-client — Guide pour Claude

> ⚠️ **Fichier auto-généré** depuis
> `vendor/axelraboit/aurora/.claude/client_template/CLAUDE.md` à chaque
> `make aurora-update`. Toute modification locale sera **écrasée**. Pour
> modifier le contenu : éditer le template dans aurora-core puis commit
> + push, et lancer `make aurora-update` côté client.
>
> Si tu veux ajouter du contenu **spécifique au projet client** (qui ne
> doit pas être écrasé) : créer `CLAUDE.local.md` à côté de ce fichier
> et le référencer dans la section dédiée plus bas.

App Symfony cliente qui consomme `axelraboit/aurora` (aurora-core) comme
bundle composer + assets npm. Sert d'**exemple canonique** de comment
étendre Aurora pour un usage métier.

---

## 📚 Base de mémoire (symlinks vers vendor via `make aurora-update`)

Les mémoires sont des **symlinks** vers `vendor/axelraboit/aurora/.claude/memory/`
créés par `make aurora-update`. Elles restent toujours en phase avec la version
installée d'aurora-core. **Ne pas éditer ces fichiers** — ils vivent dans vendor.

### Contexte aurora-core (conventions internes du bundle)
[`.claude/memory/aurora-core/MEMORY.md`](.claude/memory/aurora-core/MEMORY.md)
— conventions, décisions, pièges et heuristiques du bundle aurora-core.
Utile pour comprendre *pourquoi* une API est faite ainsi avant de l'étendre.

### Patterns d'extension aurora-client
[`.claude/memory/aurora-client/MEMORY.md`](.claude/memory/aurora-client/MEMORY.md)
— tout pour étendre une entité, un DTO, un Manager, un Serializer, la Vue
ou un template Twig depuis un projet client.

**Checklist d'extension complète** :
[`.claude/memory/aurora-client/checklist_extend_full_entity.md`](.claude/memory/aurora-client/checklist_extend_full_entity.md)
— pas-à-pas pour étendre une entité de bout en bout.

**Pattern par couche** (5 couches Sylius) :
- [Entité](.claude/memory/aurora-client/pattern_extend_entity.md)
- [DTO](.claude/memory/aurora-client/pattern_extend_dto.md)
- [Manager](.claude/memory/aurora-client/pattern_extend_manager.md)
- [Serializer](.claude/memory/aurora-client/pattern_extend_serializer.md)
- [Vue](.claude/memory/aurora-client/pattern_extend_vue.md)
- [Twig override](.claude/memory/aurora-client/pattern_override_twig.md)
- [Repository](.claude/memory/aurora-client/pattern_extend_repository.md)

**Pièges à connaître** :
- [Toujours override `create<X>()` quand on étend une entité](.claude/memory/aurora-client/pitfall_create_hook_required.md)
- [Toujours `parent::applyInput()` AVANT d'ajouter ses setters](.claude/memory/aurora-client/pitfall_call_parent_apply_input.md)

---

## Quand ajouter une nouvelle mémoire ?

Si pendant une session tu rencontres :
- Un **nouveau pattern client** (cas non couvert par les fichiers ci-dessus)
- Un **piège côté client** (config Symfony, conflit DI, schéma migration, etc.)
- Une **décision spécifique au client** (ex: choix d'architecture interne au
  projet client, conventions équipe)

→ Trois options selon le scope :

1. **Pattern d'extension Aurora utilisable par tous les clients** : ajouter
   à `aurora-core/.claude/memory/aurora-client/` + commit + push côté aurora-core.
   Sera disponible chez tous les clients via vendor au prochain `make aurora-update`
   (symlinké depuis `.claude/memory/aurora-client/`).

2. **Convention/pattern interne au projet client** (pas réutilisable) :
   créer une mémoire **locale** ici dans `aurora-client/.claude/memory/`
   (à créer si pas existant) avec son propre `MEMORY.md`. Référencer ce
   fichier ici dans CLAUDE.md.

3. **Préférence personnelle de l'utilisateur** : remonter à la mémoire
   user-level (Claude le fera automatiquement si la préférence est
   inter-projets).

---

## Commandes utiles

```bash
# Symfony
php bin/console cache:clear
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
php bin/console debug:container <ServiceName>  # vérifier qu'un AsAlias prend bien

# Tests
php bin/phpunit

# Assets
npm run dev
npm run build

# Mettre à jour aurora-core (pull les nouvelles mémoires aussi)
make aurora-update
```

---

## Mémoire locale du projet client (optionnelle)

Si ce projet client a des conventions / pièges / décisions **spécifiques**
qui ne doivent pas être écrasés au prochain `aurora-update`, créer
`CLAUDE.local.md` à côté de ce fichier. Claude Code charge automatiquement
les deux (CLAUDE.md auto-généré + CLAUDE.local.md custom).

Recommandation : `CLAUDE.local.md` doit lister les conventions internes
qui n'ont rien à voir avec aurora-core (architecture du projet, conventions
équipe, intégrations tierces spécifiques, etc.).

---

## Targets Makefile spécifiques au client (optionnel)

Le `Makefile` est synchronisé depuis aurora-core et **écrasé** à chaque
`make aurora-update`. Pour ajouter des targets propres au projet client
sans les perdre :

1. Créer un `Makefile.local` à la racine du projet client.
2. Y mettre les targets custom :
   ```makefile
   deploy-staging:
       ./bin/deploy.sh staging

   reset-fixtures:
       php bin/console doctrine:fixtures:load --no-interaction
   ```
3. Le Makefile principal fait `-include Makefile.local` à la fin → les
   targets sont disponibles via `make deploy-staging` etc. comme s'ils
   étaient dans le Makefile principal.

`Makefile.local` n'est **jamais** touché par `sync-makefile`.

