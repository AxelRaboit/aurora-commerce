# Structure d'un module / sous-module dans `src/`

## Règle

Chaque entité Aurora est rangée dans un **dossier dédié** sous `src/Core/`
ou `src/Module/<Module>/<Feature>/`, avec une structure standard :

```
src/Core/<Feature>/                            ← ex: src/Core/Agency/
src/Module/<Module>/<Feature>/                  ← ex: src/Module/Editorial/Post/
├── Controller/
│   ├── Backend/                                ← endpoints admin (/backend/...)
│   └── Front/                                  ← endpoints frontend (sans /backend)
├── Dto/                                        ← Input + InputInterface + InputFactory + InputFactoryInterface
├── Entity/                                     ← Interface + Abstract + concrete
├── Enum/                                       ← enums backed (StatusEnum, etc.)
├── Manager/                                    ← Manager + ManagerInterface
├── Repository/                                 ← <Name>Repository extends ResolveTargetEntityRepository
├── Serializer/                                 ← Serializer + SerializerInterface
├── Service/                                    ← logique stateless (calculs, validateurs, providers)
├── View/                                       ← <Plural>ViewBuilder pour l'admin Twig
├── Message/                                    ← messages async (Symfony Messenger), si applicable
├── MessageHandler/                             ← handlers async
├── EventSubscriber/                            ← listeners Symfony, si applicable
├── Twig/                                       ← TwigExtension custom, si applicable
└── Security/                                   ← Voters, AccessChecker, si applicable
```

## Pourquoi cette séparation

- **Cohésion verticale** : tout ce qui concerne `Agency` est sous
  `src/Core/Agency/`. Pas de répartition horizontale par type
  (`src/Controller/`, `src/Manager/`, …).
- **Module isolé** : on peut ajouter / supprimer un module entier en
  ajoutant / supprimant un dossier `src/Module/<Module>/`.
- **Découverte** : navigation IDE simple — chercher `Post` ouvre
  `src/Module/Editorial/Post/`.

## Comment l'appliquer

### Choisir Core ou Module ?

- **Core** : infrastructure transversale, indépendante d'un domaine métier
  (Agency, Service, User, Media, Menu, Theme, Setting, Locale, Audit,
  Notification, Sequence, Validation).
- **Module** : domaine métier (Editorial, Crm, Erp, Ecommerce, Photo,
  Billing, Ged, Project).

Si une entité touche du métier, c'est `src/Module/<Module>/<Entité>/`.
Sinon `src/Core/<Entité>/`.

### Sous-dossiers obligatoires vs optionnels

**Toujours** : `Entity/`, `Repository/`.

**Si admin CRUD** (entité instrumentée, cf
[`convention_extensibility.md`](convention_extensibility.md)) : `Dto/`,
`Manager/`, `Serializer/`, `Controller/Backend/`, `View/`.

**Selon besoin** : `Service/` (helpers stateless), `Message/`+
`MessageHandler/` (async), `EventSubscriber/`, `Twig/`, `Security/`,
`Controller/Front/`, `Enum/`.

### Naming des fichiers

- Singulier pour les entités : `Agency.php`, `Post.php`.
- Singulier pour les `<Name>Manager.php`, `<Name>Repository.php`,
  `<Name>Serializer.php`, `<Name>Input.php`.
- Pluriel pour les Controllers + ViewBuilders : `AgenciesController.php`,
  `AgenciesViewBuilder.php` (cohérent avec le pluriel des routes
  `/backend/agencies`).

## Exemples

### Standard simple (Agency)

```
src/Core/Agency/
├── Controller/Backend/AgenciesController.php
├── Dto/{Agency,AgencyInputFactory}{,Interface}.php   (4 fichiers)
├── Entity/{Agency,AbstractAgency,AgencyInterface}.php
├── Manager/{AgencyManager,AgencyManagerInterface}.php
├── Repository/AgencyRepository.php
├── Serializer/{AgencySerializer,AgencySerializerInterface}.php
└── View/AgenciesViewBuilder.php
```

### Module avec Front + Backend (Post)

```
src/Module/Editorial/Post/
├── Controller/Backend/{PostsController,PostTypesController}.php
├── Controller/Front/PageController.php
├── Dto/...
├── Entity/...
├── Enum/{PostStatusEnum,PostFieldTypeEnum}.php
├── Manager/{PostManager,PostTypeManager,PostManagerInterface,PostTypeManagerInterface}.php
├── Message/{ProcessPostScheduledMessage}.php       ← async
├── MessageHandler/{ProcessPostScheduledHandler}.php
├── Repository/...
├── Security/PostVoter.php                          ← ACL specifique
├── Serializer/...
├── Service/{PostTextExtractor,PostSlugGenerator}.php
└── View/{PostsViewBuilder,PostTypesViewBuilder}.php
```

## Anti-patterns

- ❌ Mélanger plusieurs entités dans un même dossier (ex:
  `src/Module/Editorial/{Post,Comment,Form}` partagent un dossier
  `Controller/`)
- ❌ Casser la convention pour gagner 1 ligne d'import (ex: mettre
  `PostInput` à côté de `PostManager` au lieu de `Dto/`)
- ❌ Créer un sous-dossier optionnel vide (`Service/` qui ne contient rien
  → ne pas créer)
