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

## 📚 Base de mémoire (lue depuis aurora-core via composer)

La mémoire projet vit côté **aurora-core** et est distribuée via composer.
**Pas de duplication** : une mise à jour de la convention met
automatiquement à jour les patterns côté client.

**Index principal** :
[`vendor/axelraboit/aurora/.claude/memory/MEMORY.md`](vendor/axelraboit/aurora/.claude/memory/MEMORY.md)
(conventions générales aurora-core qu'il est utile de connaître côté client).

**Patterns d'extension** :
[`vendor/axelraboit/aurora/.claude/memory/client/MEMORY.md`](vendor/axelraboit/aurora/.claude/memory/client/MEMORY.md)
— c'est ici qu'on trouve tout pour étendre une entité, un DTO, un Manager,
un Serializer, la Vue ou un template Twig.

**Checklist d'extension complète** :
[`vendor/axelraboit/aurora/.claude/memory/client/checklist_extend_full_entity.md`](vendor/axelraboit/aurora/.claude/memory/client/checklist_extend_full_entity.md)
— pas-à-pas pour étendre une entité de bout en bout.

**Pattern par couche** (5 couches Sylius) :
- [Entité](vendor/axelraboit/aurora/.claude/memory/client/pattern_extend_entity.md)
- [DTO](vendor/axelraboit/aurora/.claude/memory/client/pattern_extend_dto.md)
- [Manager](vendor/axelraboit/aurora/.claude/memory/client/pattern_extend_manager.md)
- [Serializer](vendor/axelraboit/aurora/.claude/memory/client/pattern_extend_serializer.md)
- [Vue](vendor/axelraboit/aurora/.claude/memory/client/pattern_extend_vue.md)
- [Twig override](vendor/axelraboit/aurora/.claude/memory/client/pattern_override_twig.md)
- [Repository](vendor/axelraboit/aurora/.claude/memory/client/pattern_extend_repository.md)

**Pièges à connaître** :
- [Toujours override `create<X>()` quand on étend une entité](vendor/axelraboit/aurora/.claude/memory/client/pitfall_create_hook_required.md)
- [Toujours `parent::applyInput()` AVANT d'ajouter ses setters](vendor/axelraboit/aurora/.claude/memory/client/pitfall_call_parent_apply_input.md)

---

## Quand ajouter une nouvelle mémoire ?

Si pendant une session tu rencontres :
- Un **nouveau pattern client** (cas non couvert par les fichiers ci-dessus)
- Un **piège côté client** (config Symfony, conflit DI, schéma migration, etc.)
- Une **décision spécifique au client** (ex: choix d'architecture interne au
  projet client, conventions équipe)

→ Trois options selon le scope :

1. **Pattern d'extension Aurora utilisable par tous les clients** : ajouter
   à `vendor/axelraboit/aurora/.claude/memory/client/` + commit côté
   aurora-core. Sera distribué automatiquement à tous les clients via
   `composer update`.

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

