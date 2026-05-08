# Convention — Rendre une entité Aurora extensible par les clients

**Audience** : contributeurs d'aurora-core (pas les développeurs côté client).

Ce document décrit la convention à suivre pour qu'**une entité d'aurora-core
puisse être étendue de bout en bout par un client** (ajout d'un champ
persistable, validable, sérialisé, et éditable depuis le backoffice) **sans
qu'aucun fichier de `vendor/aurora/` soit modifié côté client**.

L'entité de référence est **Agency** — toute nouvelle entité Aurora doit
suivre ce pattern, et toute entité existante doit y être migrée si elle a
vocation à être étendue par un client.

> Pour le côté client (comment *consommer* cette extensibilité), voir
> [`extending_agency_pilot.md`](./extending_agency_pilot.md). Ce document-ci
> ne traite que de **comment l'exposer** depuis aurora-core.

---

## 1. Rationale

Sans cette convention, un client qui veut ajouter un champ `code` à `Agency`
doit dupliquer (forker) :

- L'entité concrète (avec tous les setters/getters)
- Le DTO d'entrée + sa logique `fromArray()`
- Le Manager (avec sa logique de persist + audit log)
- Le Serializer
- Le composant Vue de la page admin
- Le template Twig

C'est ingérable : à chaque mise à jour d'aurora-core, le client doit
manuellement réconcilier ses copies forkées avec les changements upstream.

Le pattern Sylius (qu'Aurora suit) répond à ce problème : aurora-core
expose des **points d'extension typés** (interfaces, hooks `protected`,
factories, slots Vue scoped), et le client n'écrit que **le delta** —
les setters de son champ, l'appel `parent::applyInput()`, le slot Vue qui
ajoute son input, etc.

---

## 2. Quand appliquer cette convention ?

**Critère unique et net** : *l'entité a-t-elle une page admin CRUD autonome,
avec un tableau ET un formulaire de création/édition dédié ?*

- **Oui** → appliquer le pattern complet (5 couches)
- **Non** (gérée via le formulaire d'un parent, ou auto-générée, ou sans UI
  admin) → seul le niveau 1 (entité substituable via `resolve_target_entities`)
  est requis

### 2.1 Entités à instrumenter (30)

| Module | Entités |
|---|---|
| Core | `Agency`, `Locale`, `Media`, `MediaFolder`, `Menu`, `Notification`, `Service`, `Setting`, `Theme`, `User` |
| Editorial | `Comment`, `Form`, `Post`, `PostType`, `Taxonomy` |
| Crm | `Company`, `Contact`, `Deal` |
| Erp | `Product` |
| Ecommerce | `Listing`, `Order` |
| Photo | `Gallery` |
| Billing | `Invoice`, `Tiers`, `OcrJob` |
| Ged | `Document`, `DocumentCategory` |
| Project | `Project`, `ProjectTask` |

### 2.2 Entités à exclure (≈ 30)

| Catégorie | Entités |
|---|---|
| Translations (gérées via parent) | toutes les `*Translation` |
| Items / lignes inline | `CartItem`, `OrderLine`, `InvoiceLine`, `FormField`, `PostTypeField`, `ProjectTaskItem`, `ProjectTaskComment`, `ProjectTaskTimeEntry`, `GalleryItem`, `GalleryItemComment`, `GalleryPick`, `GalleryFinalization`, `GalleryInvite`, `CommentReaction` |
| Audit / historique auto-générés | `AuditLog`, `PostRevision`, `PostSlugHistory`, `FormSubmission` |
| Auth tunnel sans page admin | `AccessRequest`, `ResetPasswordRequest` |
| Configs gérées inline dans le parent | `MenuItem`, `TaxonomyTerm`, `ProjectColumn`, `ProjectLabel`, `ProjectSprint`, `ProjectSavedView` |
| Sessions runtime (pas de page admin) | `Cart` |

Pour ces entités, **seul le niveau 1 est requis** : pattern
`Interface + AbstractX + concrete` + `resolve_target_entities`.
Pas de DTO, pas de Manager extensible, pas de Vue slots.

---

## 3. Les 5 couches du pattern complet

Pour une entité éligible (= avec page admin), exposer **toutes** les couches
ci-dessous. Ne pas en oublier une.

### Couche 1 — Entité Doctrine

**Toujours** (déjà appliqué sur les 62 entités Aurora) :

```
Aurora\<Module>\<Feature>\Entity\
├── <Name>Interface.php       # contrat public (getters/setters)
├── Abstract<Name>.php         # MappedSuperclass Doctrine — toutes les colonnes sauf id
└── <Name>.php                 # entité concrète, non-final, juste id + sequence
```

Convention :
- `<Name>Interface` étend `Aurora\Core\Contract\TimestampableInterface` si
  l'entité utilise `TimestampableTrait`
- `Abstract<Name>` est `#[ORM\MappedSuperclass]`, contient TOUTES les colonnes
  **sauf** l'`id` et la sequence (Doctrine ne propage pas les `SequenceGenerator`
  au MappedSuperclass)
- `<Name>` est `#[ORM\Entity]` non-`final`, contient uniquement l'`id` +
  `SequenceGenerator` + les ManyToMany éventuelles (Doctrine ne supporte pas
  les ManyToMany sur MappedSuperclass de manière propre)
- Sequence nommée `seq_core_<entity>_id` — **règle dure**, le préfixe `core_`
  est obligatoire pour éviter les collisions avec des entités client
  homonymes (un client tracking app peut très bien avoir sa propre table
  `projects` avec son propre `seq_project_id` ; toutes les sequences Aurora
  doivent vivre sous leur propre namespace `seq_core_*`)
- Référencé dans `src/AuroraBundle.php::$resolve_target_entities`

### Couche 2 — DTO d'entrée + Factory

**Si** l'entité est créée/éditée via un formulaire admin :

```
Aurora\<Module>\<Feature>\DTO\
├── <Name>InputInterface.php          # contrat — getters utilisés par le Manager
├── <Name>Input.php                    # implémentation par défaut, non-final, readonly
├── <Name>InputFactoryInterface.php   # contrat de la factory
└── <Name>InputFactory.php            # factory par défaut, #[AsAlias(<Name>InputFactoryInterface::class)]
```

**Pourquoi une factory ?** Le controller ne peut pas faire `new <Name>Input(...)`
en dur — sinon le client ne peut pas substituer son propre DTO. La factory
est un service injectable que le client peut décorer via `#[AsAlias]`.

Le contrat `<Name>InputInterface` expose les **getters** des champs requis
par Aurora (typiquement `getName(): string`). Pas de setters — le DTO est
immutable.

`<Name>Input` est :
- `readonly class` (PAS `final readonly` — le client doit pouvoir étendre)
- Implémente `<Name>InputInterface`
- Constructeur en property promotion avec annotations `#[Assert\*]` Symfony
  pour la validation

`<Name>InputFactory` :
- Implémente `<Name>InputFactoryInterface`
- A `#[AsAlias(<Name>InputFactoryInterface::class)]` pour devenir le service
  par défaut
- Une seule méthode : `fromArray(array $data): <Name>InputInterface`
- Utilise `Aurora\Core\Support\Str::trimFromArray($data, 'name')` pour le
  parsing standard

### Couche 3 — Manager

**Si** l'entité a un Manager (création + update + delete via un service) :

```
Aurora\<Module>\<Feature>\Manager\
├── <Name>ManagerInterface.php   # contrat des opérations métier (create/update/delete)
└── <Name>Manager.php             # implémentation par défaut, #[AsAlias(<Name>ManagerInterface::class)]
```

**Pourquoi des hooks `protected` ?** Pour que le client n'ait à override que
le strict minimum (instanciation + hydratation), pas toute la mécanique de
persist + audit log.

`<Name>Manager` doit :
- Être non-`final` (clients étendent)
- Avoir des propriétés `protected readonly` (pas `private`) pour que les
  sous-classes y accèdent
- Avoir `#[AsAlias(<Name>ManagerInterface::class)]`
- Exposer **deux hooks `protected`** :
  - `create<Name>(): <Name>Interface` — instancie l'entité concrète
    (par défaut `new <Name>()`)
  - `applyInput(<Name>Interface $entity, <Name>InputInterface $input): void`
    — copie les champs du DTO vers l'entité (par défaut, juste les champs
    Aurora)
- `create()` et `update()` appellent ces hooks puis font le persist + flush
  + audit log

Squelette :

```php
class AgencyManager implements AgencyManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(AgencyInputInterface $input): AgencyInterface
    {
        $agency = $this->createAgency();
        $this->applyInput($agency, $input);
        $this->entityManager->persist($agency);
        $this->entityManager->flush();
        $this->auditLogger->log('core', 'agency.created', 'Agency', $agency->getId(), ['name' => $agency->getName()]);
        return $agency;
    }

    public function update(AgencyInterface $agency, AgencyInputInterface $input): void
    {
        $this->applyInput($agency, $input);
        $this->entityManager->flush();
        $this->auditLogger->log('core', 'agency.updated', 'Agency', $agency->getId(), ['name' => $agency->getName()]);
    }

    public function delete(AgencyInterface $agency): void { /* … */ }

    protected function createAgency(): AgencyInterface
    {
        return new Agency();
    }

    protected function applyInput(AgencyInterface $agency, AgencyInputInterface $input): void
    {
        $agency->setName($input->getName());
    }
}
```

### Couche 4 — Serializer

**Si** l'entité est sérialisée en JSON pour le front :

```
Aurora\<Module>\<Feature>\Serializer\
├── <Name>SerializerInterface.php   # contrat — méthode serialize()
└── <Name>Serializer.php             # implémentation par défaut, #[AsAlias(...)]
```

`<Name>Serializer` doit :
- Être non-`final`
- Avoir `#[AsAlias(<Name>SerializerInterface::class)]`
- Exposer une méthode `serialize(<Name>Interface $entity): array` qui
  retourne le payload JSON minimal d'Aurora

Le client étend cette classe et override `serialize()` pour ajouter ses
propres champs au tableau retourné par `parent::serialize($entity)`.

### Couche 5 — Vue + Twig

**Si** l'entité a une page admin tableau + formulaire :

#### 5.1 Composant Vue principal

`assets/<Module>/backend/<plural>/<Plural>App.vue` doit exposer :

- **Slots scoped** :
  - `extra-headers` (sans scope) — `<th>` additionnels pour la table
  - `extra-cells` (scoped sur `agency` — ou nom équivalent) — `<td>` par ligne
  - `extra-form-fields` (scoped sur `editForm` + `errors`) — inputs additionnels
    dans le modal de création/édition

- **Prop `extraFields`** (défaut `{}`) — config passée au composable :
  ```js
  {
      <fieldName>: {
          default: <valeur initiale>,
          fromAgency: (entity) => <valeur depuis l'entité>,
      },
  }
  ```

#### 5.2 Composable `useXxxEdit`

Doit :
- Accepter `options.extraFields` en 4ème argument
- Initialiser `editForm` avec `name` (ou clé Aurora par défaut) **+ les clés
  des `extraFields`**
- Implémenter `resetExtras()` (utilisé par `openCreate`) et
  `loadExtrasFrom(entity)` (utilisé par `openEdit`)
- Envoyer le payload via `request(url, { ...editForm })` (spread !) — pas
  `{ name: editForm.name }` en dur, sinon les champs client ne sont pas envoyés

> ⚠️ **`editForm` doit rester strict** — uniquement les champs primitifs
> envoyés au backend (string, number, boolean, array de primitives). Le
> spread `{ ...editForm }` sérialise *tout* ce qu'il contient ; toute valeur
> non prévue pollue le payload et casse la validation Aurora.

✅ Correct — uniquement les champs de l'entité :

```js
const editForm = reactive({
    name: "",       // déclaré côté Aurora
    code: "",       // ajouté via extraFields côté client
    address: "",    // ajouté via extraFields côté client
});
// submit envoie : { name, code, address } ✓
```

❌ À éviter — formes courantes de pollution :

```js
// 1. Computed property dans le reactive
const editForm = reactive({
    name: "",
    code: "",
    get displayLabel() { return `${this.name} (${this.code})`; },
});
// → submit envoie un displayLabel parasite

// 2. État UI mélangé
const editForm = reactive({
    name: "",
    isDirty: false,              // état UI, pas un champ d'entité
    lastSavedAt: new Date(),     // Date non sérialisable proprement
});
// → champs parasites en base ou validation cassée

// 3. Refs imbriqués
const editForm = reactive({
    name: "",
    selectedTags: ref([]),       // ref dans un reactive
});
// → sérialisation imprévisible selon le runtime Vue
```

Pour l'état UI / computed / refs, utiliser un `reactive` ou des `ref()`
**séparés** — pas `editForm`.

#### 5.3 Override Twig automatique

Aucun changement nécessaire côté aurora-core — le bundle prepend
automatiquement `kernel.project_dir/templates/<Namespace>/` devant son propre
chemin sous chaque namespace `@<Namespace>`. Le client met simplement son
override dans `templates/Core/backend/<plural>/index.html.twig` (ou
`templates/<Module>/...`) et c'est résolu en priorité.

### Couche bonus — ResolveTargetEntityRepository

Le `<Name>Repository` doit étendre
`Aurora\Core\Repository\ResolveTargetEntityRepository` (et non pas
`ServiceEntityRepository`) :

```php
class AgencyRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agency::class, AgencyInterface::class);
    }
}
```

Sans ça, le constructor du `ServiceEntityRepository` hardcode la classe
concrete Aurora → le repo continue de query la table Aurora même quand le
client a substitué l'entité. **Tous les repos Aurora ont déjà été migrés.**

---

## 4. Conventions de nommage

Pour `<Name> = Agency` :

| Élément | Nom |
|---|---|
| Entité concrète | `Agency` (`Aurora\Core\Agency\Entity\Agency`) |
| Mapped superclass | `AbstractAgency` |
| Interface entité | `AgencyInterface` |
| Table | `core_agencies` |
| Sequence | `seq_core_agency_id` |
| DTO d'entrée | `AgencyInput` |
| Interface DTO | `AgencyInputInterface` |
| Factory | `AgencyInputFactory` |
| Interface factory | `AgencyInputFactoryInterface` |
| Manager | `AgencyManager` |
| Interface Manager | `AgencyManagerInterface` |
| Serializer | `AgencySerializer` |
| Interface Serializer | `AgencySerializerInterface` |
| Repository | `AgencyRepository` |
| Vue main app | `AgenciesApp.vue` |
| Composable edit | `useAgenciesEdit.js` (avec option `extraFields`) |
| Hooks Manager | `createAgency()` + `applyInput()` (`protected`) |
| Vue slots | `extra-headers`, `extra-cells`, `extra-form-fields` |

**Exception** : pour `User`, l'interface s'appelle `CoreUserInterface` (et
non `UserInterface`) car Symfony utilise déjà `UserInterface` dans son
namespace `Symfony\Component\Security\Core\User\UserInterface`.

---

## 5. Anti-patterns à éviter

❌ **`final readonly class <Name>Input`** — empêche l'extension
✅ `readonly class <Name>Input implements <Name>InputInterface`

❌ **`final class <Name>Manager`** — empêche l'extension
✅ `class <Name>Manager implements <Name>ManagerInterface`

❌ **`private readonly EntityManagerInterface $entityManager`** dans le Manager
✅ `protected readonly EntityManagerInterface $entityManager` (sous-classes
   ont besoin d'y accéder)

❌ **Static `<Name>Input::fromArray($data)`** appelé directement dans le
   controller — non-décorable
✅ `<Name>InputFactoryInterface` injecté + `$this->factory->fromArray($data)`

❌ **`new <Name>()` directement dans `Manager::create()`** — non-overridable
✅ `$this->create<Name>()` (hook `protected`) — override-able

❌ **Vue submit en dur** : `request(url, { name: editForm.name })`
✅ `request(url, { ...editForm })` — spread tout le form, les champs client
   sont envoyés automatiquement

❌ **Repository extends `ServiceEntityRepository` directement**
✅ Repository extends `Aurora\Core\Repository\ResolveTargetEntityRepository`

❌ **Sequence nommée `seq_<entity>_id`** (sans `core_`) — collision potentielle
   avec des entités client homonymes
✅ Sequence nommée `seq_core_<entity>_id`

❌ **`<Name>Manager::create()` qui contourne `applyInput()`** — fait perdre
   au client la capacité d'override la logique d'hydratation
✅ Toujours passer par les hooks `create<Name>()` + `applyInput()`

---

## 6. Checklist — Retrofitter une entité existante

Pour appliquer cette convention à une entité qui n'a pas encore le pattern
complet (ex : Deal, Post, User, Project, Contact, Company, Order, etc.) :

### Côté code

- [ ] Vérifier que le pattern entité (Interface + AbstractX + concrete) est
      déjà en place. Sinon, le faire d'abord (cf. couche 1).
- [ ] Vérifier que le repository étend `ResolveTargetEntityRepository`. Sinon,
      le faire d'abord (cf. couche bonus).
- [ ] Sequence renommée `seq_core_*` ?
- [ ] **DTO** :
  - Créer `<Name>InputInterface.php`
  - Retirer `final` de `<Name>Input.php`, ajouter `implements <Name>InputInterface`
  - Créer `<Name>InputFactoryInterface.php` + `<Name>InputFactory.php` avec
    `#[AsAlias]`
  - Retirer la méthode statique `fromArray()` de `<Name>Input` (la déplacer
    dans la factory)
- [ ] **Manager** :
  - Créer `<Name>ManagerInterface.php`
  - Retirer `final readonly` de `<Name>Manager.php` → `class`
  - Changer `private readonly` → `protected readonly` sur les propriétés DI
  - Ajouter `#[AsAlias(<Name>ManagerInterface::class)]`
  - Refactor `create()` / `update()` pour passer par les hooks
    `protected create<Name>()` + `protected applyInput()`
- [ ] **Serializer** :
  - Créer `<Name>SerializerInterface.php`
  - Retirer `final readonly` de `<Name>Serializer.php` → `class`
  - Ajouter `#[AsAlias(<Name>SerializerInterface::class)]`
  - Implémenter `<Name>SerializerInterface`
- [ ] **Controller** : remplacer les imports concrets par les interfaces dans
      le constructeur du controller :
  ```php
  protected readonly <Name>SerializerInterface $serializer,
  protected readonly <Name>ManagerInterface $manager,
  protected readonly <Name>InputFactoryInterface $inputFactory,
  ```
- [ ] **Vue** :
  - Refactor `<Plural>App.vue` pour exposer les 3 slots scoped + prop
    `extraFields`
  - Refactor `useXxxEdit.js` pour accepter `options.extraFields`,
    pré-initialiser `editForm`, et envoyer `{ ...editForm }` au submit
  - Vérifier qu'aucun composant Vue ne hardcode la liste des colonnes /
    champs

### Côté validation

- [ ] `php bin/console cache:clear`
- [ ] `php bin/console doctrine:schema:validate`
- [ ] `vendor/bin/phpunit` — 494 tests doivent rester verts
- [ ] Lancer la page admin Aurora correspondante en navigateur, créer/éditer
      une entité — comportement Aurora inchangé

### Côté doc

- [ ] Si nouvelle entité : ajouter dans la convention si elle introduit un
      cas pas couvert ici
- [ ] Si gap dans cette convention découvert pendant le retrofit : le
      documenter

---

## 7. Checklist — Créer une nouvelle entité Aurora

Pour une nouvelle entité créée from-scratch dans aurora-core :

- [ ] Créer les 3 fichiers d'entité (`Interface` + `Abstract<Name>` + `<Name>`)
- [ ] Sequence Postgres en `seq_core_<entity>_id`
- [ ] Référencer dans `src/AuroraBundle.php::$resolve_target_entities`
- [ ] Repository qui étend `ResolveTargetEntityRepository`
- [ ] Migration Doctrine standard
- [ ] **Si l'entité a une page admin CRUD** :
  - Créer les 4 fichiers DTO (Input + InputInterface + Factory +
    FactoryInterface)
  - Créer les 2 fichiers Manager (Manager + ManagerInterface) avec hooks
    `create<Name>()` + `applyInput()`
  - Créer les 2 fichiers Serializer (Serializer + SerializerInterface)
  - Controller qui injecte les 3 interfaces (factory + manager + serializer)
  - Vue : `<Plural>App.vue` avec les 3 slots `extra-*` + prop `extraFields`
  - Composable `useXxxEdit.js` qui accepte `extraFields` et fait le spread
    au submit

- [ ] Suivre les conventions de nommage section 4
- [ ] Ne pas tomber dans les anti-patterns section 5

---

## 8. Référence canonique

Pour copier-coller un exemple en bon état, partir de **`Agency`** :

| Couche | Fichiers de référence |
|---|---|
| Entity | `src/Core/Agency/Entity/{AgencyInterface,AbstractAgency,Agency}.php` |
| DTO | `src/Core/Agency/DTO/{AgencyInputInterface,AgencyInput,AgencyInputFactoryInterface,AgencyInputFactory}.php` |
| Manager | `src/Core/Agency/Manager/{AgencyManagerInterface,AgencyManager}.php` |
| Serializer | `src/Core/Agency/Serializer/{AgencySerializerInterface,AgencySerializer}.php` |
| Repository | `src/Core/Agency/Repository/AgencyRepository.php` |
| Controller | `src/Core/Agency/Controller/Backend/AgenciesController.php` |
| Vue main | `assets/Core/backend/agencies/AgenciesApp.vue` |
| Vue composables | `assets/Core/backend/agencies/composables/useAgenciesEdit.js` |
| Twig | `templates/Core/backend/agencies/index.html.twig` |

Toute déviation de ce pattern doit être justifiée (cas spécifique au domaine
de l'entité) et documentée dans cette même convention.
