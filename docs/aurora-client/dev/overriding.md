# Étendre / Override aurora-core depuis le projet client

Ce document est le **recettier** pour étendre n'importe quel point
d'extension d'aurora-core depuis votre projet client. Chaque section traite
un cas concret (« je veux X → voici le diff minimal côté client »). Pour la
théorie complète du pattern Sylius en 5 couches, voir la doc canonique
[`../../aurora-core/dev/entity_extensibility_convention.md`](../../aurora-core/dev/entity_extensibility_convention.md).
Pour un tutoriel pas-à-pas qui ajoute un champ `code` à `Agency`, voir
[`../../aurora-core/dev/extending_agency_pilot.md`](../../aurora-core/dev/extending_agency_pilot.md).

> **Règle d'or** : on **n'édite jamais** un fichier sous
> `vendor/axelraboit/aurora/`. Toute modification passe par les points
> d'extension décrits ci-dessous.

---

## 0. Vue d'ensemble — les 5 couches

| Couche | Mécanisme côté client | Section |
|---|---|---|
| 1 — Entité Doctrine | `extends Abstract<Name>`, `repositoryClass`, `resolve_target_entities` | [§1](#1-layer-1--entité-doctrine) |
| 2 — DTO d'entrée + Factory | `extends <Name>Input` + `#[AsAlias(<Name>InputFactoryInterface::class)]` sur la factory client | [§2](#2-layer-2--dto-dentrée-et-factory) |
| 3 — Manager | `extends <Name>Manager` + `#[AsAlias(<Name>ManagerInterface::class)]` + override des hooks `protected` | [§3](#3-layer-3--manager) |
| 4 — Serializer | `extends <Name>Serializer` + `#[AsAlias(<Name>SerializerInterface::class)]` | [§4](#4-layer-4--serializer) |
| 5 — Vue admin | prop `extraFields` + slots scoped `extra-headers` / `extra-cells` / `extra-form-fields` | [§5](#5-layer-5--vue-admin) |

Hors des 5 couches :

- **Twig** — namespace prepend automatique. Voir [§6](#6-override-twig).
- **Repository finders custom** — pattern simple sans interface aurora-core. Voir [§7](#7-finder-method-custom).
- **Décorer un service Aurora autre** (event listener, helper) — voir [§8](#8-décorer-un-service-aurora-arbitraire).
- **Permissions / module toggles** — voir [§9](#9-permissions-et-modules).

---

## 1. Layer 1 — Entité Doctrine

### Cas : je veux ajouter un champ à `Agency`.

**Diff côté client** :

`src/Module/Core/Agency/Entity/Agency.php` :

```php
namespace App\Module\Core\Agency\Entity;

use Aurora\Core\Agency\Entity\{AbstractAgency, AgencyInterface};
use Aurora\Core\Agency\Repository\AgencyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgencyRepository::class)]
#[ORM\Table(name: 'app_agencies')]
class Agency extends AbstractAgency implements AgencyInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_app_agency_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = null;

    public function getId(): ?int { return $this->id; }
    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $code): static { $this->code = $code; return $this; }
}
```

`config/packages/doctrine.yaml` — ajouter la ligne `resolve_target_entities` :

```yaml
doctrine:
    orm:
        resolve_target_entities:
            Aurora\Core\Agency\Entity\AgencyInterface: App\Module\Core\Agency\Entity\Agency
```

### Règles dures à respecter

- On étend **`AbstractAgency`** (le `MappedSuperclass`), pas la classe
  concrète `Aurora\Core\Agency\Entity\Agency`. Sinon Doctrine exige une
  inheritance type + discriminator — non voulu.
- Table : préfixe **`app_*`** (jamais `core_*` qui est réservé à Aurora,
  jamais sans préfixe).
- Séquence : préfixe **`seq_app_*`** (jamais `seq_core_*`).
- L'`id` + le `SequenceGenerator` sont **redéclarés côté client** parce que
  Doctrine ne propage pas un `SequenceGenerator` à travers un
  `MappedSuperclass`.
- Pas besoin de redéclarer un repository client — `AgencyRepository` (qui
  étend `ResolveTargetEntityRepository`) querie automatiquement votre table
  dès que `resolve_target_entities` route l'interface.

Cf. [`../../aurora-core/dev/extending_agency_pilot.md`](../../aurora-core/dev/extending_agency_pilot.md)
pour le pilote complet.

### Cas : je veux substituer une entité d'un module Aurora (`Crm\Deal`, `Billing\Invoice`…)

Le chemin client miroir le namespace Aurora :

| Aurora | Client |
|---|---|
| `Aurora\Core\Agency\…` | `src/Module/Core/Agency/…` |
| `Aurora\Module\Crm\Deal\…` | `src/Module/Crm/Deal/…` |
| `Aurora\Module\Billing\Invoice\…` | `src/Module/Billing/Invoice/…` |

Le reste est identique au cas Agency.

---

## 2. Layer 2 — DTO d'entrée et Factory

### Cas : j'ai ajouté `code` à `Agency`, le formulaire admin doit l'envoyer.

**Diff côté client** :

`src/Module/Core/Agency/Dto/AgencyInput.php` — étend le DTO Aurora :

```php
namespace App\Module\Core\Agency\Dto;

use Aurora\Core\Agency\Dto\AgencyInput as AuroraAgencyInput;
use Symfony\Component\Validator\Constraints as Assert;

class AgencyInput extends AuroraAgencyInput
{
    public function __construct(
        string $name,
        #[Assert\Length(max: 50)]
        public readonly ?string $code = null,
    ) {
        parent::__construct($name);
    }

    public function getCode(): ?string
    {
        return $this->code;
    }
}
```

`src/Module/Core/Agency/Dto/AgencyInputFactory.php` — étend la factory et
écrase l'alias :

```php
namespace App\Module\Core\Agency\Dto;

use Aurora\Core\Agency\Dto\AgencyInputFactory as AuroraAgencyInputFactory;
use Aurora\Core\Agency\Dto\AgencyInputFactoryInterface;
use Aurora\Core\Agency\Dto\AgencyInputInterface;
use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AgencyInputFactoryInterface::class)]
class AgencyInputFactory extends AuroraAgencyInputFactory
{
    public function fromArray(array $data): AgencyInputInterface
    {
        return new AgencyInput(
            name: Str::trimFromArray($data, 'name'),
            code: Str::trimFromArray($data, 'code') ?: null,
        );
    }
}
```

### Règles dures

- Toujours **`public readonly` par propriété** dans le constructeur, **pas
  `readonly class`** : un parent `readonly class` empêcherait l'enfant
  d'ajouter une propriété mutable.
- **`#[AsAlias]` se met sur la Factory cliente**, qui prend la place de la
  factory Aurora pour l'interface `AgencyInputFactoryInterface`. Le DTO
  lui-même n'a pas besoin d'alias — il est instancié par la factory.
- Pas de méthode statique `fromArray()` dans le DTO — c'est la factory qui
  s'en charge (c'est elle qu'on peut décorer).
- Le contrôleur Aurora type-hint déjà `AgencyInputFactoryInterface`, donc il
  reçoit votre factory cliente sans aucune autre modification.

Sub-DTO (ex. `PostTranslationInput` inclus dans `PostInput`) : ils restent
`final readonly` et **ne sont pas instrumentés**. Pour les substituer, étendez
le DTO racine et fournissez vos propres sub-DTO via la factory cliente.

---

## 3. Layer 3 — Manager

### Cas : persister + auditer le champ `code` à la création / édition

**Diff côté client** — `src/Module/Core/Agency/Manager/AgencyManager.php` :

```php
namespace App\Module\Core\Agency\Manager;

use App\Module\Core\Agency\Dto\AgencyInput;
use App\Module\Core\Agency\Entity\Agency;
use Aurora\Core\Agency\Dto\AgencyInputInterface;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Agency\Manager\AgencyManager as AuroraAgencyManager;
use Aurora\Core\Agency\Manager\AgencyManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AgencyManagerInterface::class)]
class AgencyManager extends AuroraAgencyManager
{
    protected function createAgency(): AgencyInterface
    {
        return new Agency();
    }

    protected function applyInput(AgencyInterface $agency, AgencyInputInterface $input): void
    {
        parent::applyInput($agency, $input);

        if ($agency instanceof Agency && $input instanceof AgencyInput) {
            $agency->setCode($input->getCode());
        }
    }

    protected function auditPayload(AgencyInterface $agency): array
    {
        return [
            ...parent::auditPayload($agency),
            'code' => $agency instanceof Agency ? $agency->getCode() : null,
        ];
    }
}
```

### Trois familles de hooks à override

1. **`create<X>()`** — un hook par classe instanciée par le Manager. **Sans
   exception** : si vous étendez l'entité, vous **devez** override
   `create<X>()` sinon Doctrine `persist()` votre vieille classe Aurora et
   tous vos champs custom sont perdus à la sauvegarde. Pour
   `ProjectManager`, ça veut dire 4 hooks (`createProject`, `createProjectColumn`,
   `createProjectLabel`, `createProjectSprint`).

2. **`applyInput()`** — toujours appeler `parent::applyInput()` **d'abord**,
   sinon les champs Aurora ne sont pas hydratés.

3. **`auditCreated` / `auditUpdated` / `auditDeleted` / `auditPayload`** —
   override **uniquement** `auditPayload()` 99% du temps. Le splat-merge
   (`[...parent::auditPayload($entity), 'code' => …]`) garde les champs
   Aurora et ajoute les vôtres.

### Variante Manager à hooks multiples (User-style)

Pour `User`, il n'existe **pas** de `applyInput()` unifié — `User` a 6+
méthodes métier distinctes (`changePassword`, `consumeInvitation`,
`toggleDevRole`, `updateProfile`, …). Vous override ces méthodes
individuellement. Les hooks `create<X>()` et `audit*` restent obligatoires.
Cf. [`entity_extensibility_convention.md §4.bis.1`](../../aurora-core/dev/entity_extensibility_convention.md).

### Pièges connus

- **Oublier `create<X>()`** → Doctrine persiste la classe Aurora, perte
  silencieuse des champs client (cf. memory `pitfall_create_hook_required.md`).
- **Oublier `parent::applyInput()`** → tous les champs Aurora restent à null
  / valeur par défaut.
- Les propriétés DI dans `AgencyManager` Aurora sont `protected readonly`
  (jamais `private`) — accessibles depuis votre sous-classe.

---

## 4. Layer 4 — Serializer

### Cas : exposer `code` dans le JSON envoyé au front

**Diff côté client** —
`src/Module/Core/Agency/Serializer/AgencySerializer.php` :

```php
namespace App\Module\Core\Agency\Serializer;

use App\Module\Core\Agency\Entity\Agency;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Agency\Serializer\AgencySerializer as AuroraAgencySerializer;
use Aurora\Core\Agency\Serializer\AgencySerializerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AgencySerializerInterface::class)]
class AgencySerializer extends AuroraAgencySerializer
{
    public function serialize(AgencyInterface $agency): array
    {
        return [
            ...parent::serialize($agency),
            'code' => $agency instanceof Agency ? $agency->getCode() : null,
        ];
    }
}
```

Même pattern que le Manager : `extends`, `#[AsAlias(<I>::class)]`, spread du
parent + champs custom. C'est la couche la plus simple.

---

## 5. Layer 5 — Vue admin

Le composant Aurora `<Plural>App.vue` expose une **prop `extraFields`** + 3
slots scoped (`extra-headers`, `extra-cells`, `extra-form-fields`). Côté
client, on ne réécrit pas le composant — on le **wrap** dans
`assets/client/Overrides/`.

Exemple détaillé (slots, composables, gotchas `editForm`) :
[`assets_vue.md`](assets_vue.md) et la mémoire dédiée
[`pattern_extend_vue.md`](../../../.claude/memory/aurora-client/pattern_extend_vue.md).

Squelette d'override Vue —
`assets/client/Overrides/backend/agencies/AgenciesApp.vue` :

```vue
<script setup>
import CoreAgenciesApp from '@core/backend/agencies/AgenciesApp.vue';
</script>

<template>
  <CoreAgenciesApp :extra-fields="{ code: { default: '', fromEntity: (agency) => agency.code ?? '' } }">
    <template #extra-headers>
      <th>{{ $t('core.agencies.code') }}</th>
    </template>
    <template #extra-cells="{ agency }">
      <td>{{ agency.code }}</td>
    </template>
    <template #extra-form-fields="{ editForm, errors }">
      <AppInput v-model="editForm.code" :error="errors.code" :label="$t('core.agencies.code')" />
    </template>
  </CoreAgenciesApp>
</template>
```

> **Piège `editForm`** : `editForm` doit contenir **uniquement** les champs
> envoyés au backend (string/number/boolean). Pas d'état UI, pas de computed,
> pas de refs imbriqués. Le submit fait `request(url, { ...editForm })` —
> tout ce qui est dedans part au backend. Détail dans `assets_vue.md`.

---

## 6. Override Twig

Aucune config nécessaire. Aurora **prepend automatiquement**
`templates/<Namespace>/` du projet client devant son propre chemin sous
chaque namespace `@<Namespace>`.

Conséquence : posez votre fichier au **même chemin** que l'original en
remplaçant `vendor/axelraboit/aurora/templates/` par
`templates/` côté client :

| Template Aurora (vendor) | Override client |
|---|---|
| `@Core/backend/agencies/index.html.twig` | `templates/Core/backend/agencies/index.html.twig` |
| `@Ecommerce/backend/listings/edit.html.twig` | `templates/Ecommerce/backend/listings/edit.html.twig` |
| `@Frontend/themes/default/editorial/home.html.twig` | `templates/Frontend/themes/default/editorial/home.html.twig` |

Pour le frontend (site public), le **ThemeResolver** ajoute encore une
couche de résolution par slug de thème — voir
[`../../aurora-core/dev/frontend_theme_override.md`](../../aurora-core/dev/frontend_theme_override.md).

---

## 7. Finder method custom

Aurora **n'expose pas** d'interface `<Name>RepositoryInterface` (coût/bénéfice
non justifié — voir
[`entity_extensibility_convention.md §3 Couche bonus`](../../aurora-core/dev/entity_extensibility_convention.md)).

Le pattern côté client est simple : étendre le repo Aurora, déclarer le
`repositoryClass` sur l'entité concrète.

`src/Module/Core/Agency/Repository/AppAgencyRepository.php` :

```php
namespace App\Module\Core\Agency\Repository;

use Aurora\Core\Agency\Repository\AgencyRepository;

class AppAgencyRepository extends AgencyRepository
{
    public function findActiveExcludingArchived(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.archivedAt IS NULL')
            ->getQuery()->getResult();
    }
}
```

Puis dans l'entité cliente :

```php
#[ORM\Entity(repositoryClass: AppAgencyRepository::class)]
class Agency extends AbstractAgency implements AgencyInterface { … }
```

Aurora continue de type-hint `AgencyRepository` — la metadata résolue par
`ResolveTargetEntityRepository` route les queries vers la bonne table. Vous
type-hint `AppAgencyRepository` dans votre propre code uniquement.

---

## 8. Décorer un service Aurora arbitraire

Hors des 5 couches, deux mécanismes Symfony :

### a) Remplacer (alias)

```php
#[AsAlias(\Aurora\Core\…\SomeInterface::class)]
final class MyService implements \Aurora\Core\…\SomeInterface { … }
```

Aurora type-hint l'interface partout — votre classe prend sa place.

### b) Décorer (chaîne)

```php
#[AsDecorator(\Aurora\Core\…\SomeService::class)]
final class MyDecorator
{
    public function __construct(private readonly \Aurora\Core\…\SomeService $inner) {}

    public function doStuff(): void
    {
        // pre-hook
        $this->inner->doStuff();
        // post-hook
    }
}
```

⚠️ Décorer ne marche que si les consommateurs **type-hint l'interface**, pas
la classe concrète. Aurora le fait pour les Managers/Serializers/Factories
instrumentés ; pour le reste, lire le code avant de décorer.

### c) Écouter un événement Aurora

```php
#[AsEventListener(event: \Aurora\Core\…\Event\SomethingHappened::class)]
final class MyListener
{
    public function __invoke(SomethingHappened $event): void { … }
}
```

Placement standard : `src/EventListener/MyListener.php` (autoconfiguré) ou
`src/Module/<Mirror>/EventListener/`.

---

## 9. Permissions et modules

Pour ajouter une permission custom à un module client (ou exposer un module
au panel d'accès utilisateur) :

- **Ajouter une permission** : `ModuleInterface::getPermissions()` retourne
  un `NavPermission('mon_module.action')`. Traduction obligatoire dans
  `assets/client/locales/{fr,en}.js` sous
  `backend.permissions.names.mon_module.action`. Cf. memory
  [`pattern_add_custom_permissions.md`](../../../.claude/memory/aurora-client/pattern_add_custom_permissions.md).
- **Toggle de module utilisateur** : `ModuleToggleProviderInterface::getToggles()`
  retourne des `ModuleToggle`. Exemple complet dans
  `src/Module/Tracking/TrackingModule.php` du template. Doc core :
  [`../../aurora-core/dev/per_user_module_access.md`](../../aurora-core/dev/per_user_module_access.md).
- **Sync** après chaque ajout : `make sf CMD="aurora:privileges:sync"`.

---

## 10. Checklist d'une extension complète d'entité

Pour étendre une entité avec champ custom de bout en bout, suivre l'ordre
suivant (cf. memory
[`checklist_extend_full_entity.md`](../../../.claude/memory/aurora-client/checklist_extend_full_entity.md)) :

1. Entité cliente + `resolve_target_entities` + table `app_*` + séquence `seq_app_*`.
2. `make migration && make migrate` + `make schema-validate`.
3. DTO + Factory + `#[AsAlias]` sur la factory.
4. Manager + `#[AsAlias]` sur le manager + override `create<X>()` +
   `applyInput()` (`parent::` !) + `auditPayload()` (spread `parent::` !).
5. Serializer + `#[AsAlias]` + spread `parent::serialize()`.
6. Vue : composant override sous `assets/client/Overrides/...` avec
   `extraFields` + 3 slots.
7. Twig : override sous `templates/Core/...` si besoin.
8. `make cc` (cache:clear, indispensable après `#[AsAlias]`).
9. Test admin : créer une entité, vérifier que le champ persiste, s'audite
   et est sérialisé.

Pour les anti-patterns à éviter (DTO `readonly class`, Manager `final`,
oubli de `create<X>()`, hardcode du submit Vue…), voir la section 5 de la
convention canonique
[`entity_extensibility_convention.md`](../../aurora-core/dev/entity_extensibility_convention.md).
