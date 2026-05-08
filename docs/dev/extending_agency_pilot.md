# Étendre Agency de bout en bout (pilote)

Ce guide reproduit le **câblage complet** mis en place côté aurora-client pour
ajouter un champ `code` à l'entité `Agency` d'Aurora Core, **avec persistance,
validation, sérialisation, affichage dans le tableau backoffice et saisie dans
le formulaire de création/édition** — sans toucher à `vendor/aurora/`.

C'est le pilote du pattern d'extensibilité (Sylius-style) qui sera étendu aux
autres entités au fur et à mesure des besoins.

---

## Vue d'ensemble — 5 couches à câbler

| Couche | Fichier(s) côté client | Mécanisme |
|---|---|---|
| Entité Doctrine | `src/Entity/Agency.php` | `extends AbstractAgency`, table dédiée |
| DTO d'entrée | `src/Dto/AgencyInput.php` + `AgencyInputFactory.php` | `extends`, `#[AsAlias]` |
| Manager | `src/Manager/AgencyManager.php` | `extends`, `#[AsAlias]` |
| Serializer | `src/Serializer/AgencySerializer.php` | `extends`, `#[AsAlias]` |
| Vue + Twig | `assets/client/Overrides/backend/agencies/AgenciesApp.vue` + `templates/Core/backend/agencies/index.html.twig` | slots scoped + override Twig |

---

## 1. Entité — `App\Entity\Agency`

**Important** : on étend `AbstractAgency` (le `MappedSuperclass`), **pas** la
classe concrète `Aurora\Core\Agency\Entity\Agency`. Étendre la classe concrète
exigerait de déclarer un `#[ORM\InheritanceType]` et un discriminator column,
ce qui impose Single ou Joined Table Inheritance — pas ce qu'on veut. Le
pattern Sylius : chaque app a sa propre table.

```php
// aurora-client : src/Entity/Agency.php
namespace App\Entity;

use Aurora\Core\Agency\Entity\AbstractAgency;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Agency\Repository\AgencyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgencyRepository::class)]
#[ORM\Table(name: 'client_agencies')]
class Agency extends AbstractAgency implements AgencyInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_client_agency_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }
}
```

### 1.1 Doctrine mapping + ResolveTargetEntity

Le scaffold `bin/create-client` génère ces blocs dans
`config/packages/doctrine.yaml`. Si vous avez un projet plus ancien,
vérifiez qu'ils sont présents :

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
    orm:
        resolve_target_entities:
            Aurora\Core\Agency\Entity\AgencyInterface: App\Entity\Agency
        mappings:
            AppEntity:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
                alias: AppEntity
```

> **Pourquoi `repositoryClass: AgencyRepository::class` côté client marche
> transparent** : Aurora's repositories étendent
> `Aurora\Core\Repository\ResolveTargetEntityRepository`, qui résout l'entité
> via `getClassMetadata(<Interface>::class)` à la construction. Donc une
> seule instance de repo, mais elle querie automatiquement votre table
> `client_agencies` dès que `resolve_target_entities` route l'interface vers
> votre classe. Pas besoin de redéclarer un repository côté client (sauf si
> vous voulez ajouter vos propres méthodes — auquel cas étendez
> `Aurora\Core\Agency\Repository\AgencyRepository` et déclarez-le dans
> `config/packages/doctrine.yaml`).

À partir de cette config, **toutes** les associations Aurora qui type-hint
`AgencyInterface` (ex: `User::$agency`) résolvent automatiquement vers votre
`App\Entity\Agency`.

### 1.2 Migration — copie des données + bascule des FK

`doctrine:migrations:diff --namespace=ClientMigrations` génère une migration
brute. Elle contient des lignes parasites (ex: `DROP SEQUENCE seq_log` qui
est gérée runtime par `SequenceGenerator`, ou `DROP TABLE messenger_messages`)
qu'il faut **enlever** à la main. Voici la migration nettoyée typique :

```php
// migrations/Version20260508123924.php (nom auto-généré)
namespace ClientMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508123924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add client_agencies table extending Aurora Core Agency with code field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE seq_client_agency_id INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE client_agencies (id INT NOT NULL, name VARCHAR(150) NOT NULL, code VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');

        // Copy existing rows from core_agencies so user FKs stay valid after the switch.
        $this->addSql('INSERT INTO client_agencies (id, name, created_at, updated_at) SELECT id, name, created_at, updated_at FROM core_agencies');
        $this->addSql("SELECT setval('seq_client_agency_id', GREATEST((SELECT COALESCE(MAX(id), 0) FROM client_agencies), 1))");

        // Repoint the User → Agency FK to client_agencies.
        $this->addSql('ALTER TABLE core_users DROP CONSTRAINT fk_42028409cdeadb2a');
        $this->addSql('ALTER TABLE core_users ADD CONSTRAINT FK_42028409CDEADB2A FOREIGN KEY (agency_id) REFERENCES client_agencies (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_users DROP CONSTRAINT FK_42028409CDEADB2A');
        $this->addSql('ALTER TABLE core_users ADD CONSTRAINT fk_42028409cdeadb2a FOREIGN KEY (agency_id) REFERENCES core_agencies (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE client_agencies');
        $this->addSql('DROP SEQUENCE seq_client_agency_id CASCADE');
    }
}
```

Nuance importante : **chaque entité Aurora qui pointait `AgencyInterface`**
(ici juste `User::$agency`, mais une autre fois ce serait Photo's
`Gallery::$clientContact`, etc.) génère une `ALTER TABLE … FK` à inclure dans
la migration. Le diff Doctrine les trouve toutes.

```bash
php bin/console doctrine:migrations:diff --namespace=ClientMigrations
# Nettoyer le fichier généré (lignes seq_log, seq_prj, messenger_messages, etc.)
# Ajouter le INSERT INTO client_agencies … SELECT … FROM core_agencies
php bin/console doctrine:migrations:migrate
```

---

## 2. DTO d'entrée + Factory

### 2.1 DTO — `App\Dto\AgencyInput`

> **Note de convention** : le namespace est `App\Dto` (camelCase), **pas**
> `App\DTO`. Les directives `App\Dto\:` du `services.yaml` autoload ce dossier.

```php
// aurora-client : src/Dto/AgencyInput.php
namespace App\Dto;

use Aurora\Core\Agency\Dto\AgencyInput as AuroraAgencyInput;
use Symfony\Component\Validator\Constraints as Assert;

readonly class AgencyInput extends AuroraAgencyInput
{
    public function __construct(
        string $name,
        #[Assert\Length(max: 50, maxMessage: 'Le code dépasse 50 caractères.')]
        public ?string $code = null,
    ) {
        parent::__construct($name);
    }
}
```

Symfony Validator inspecte les attributs du DTO étendu via réflexion — le
`Assert\Length` est appliqué automatiquement, pas besoin de re-déclarer le
`Assert\NotBlank` du parent.

### 2.2 Factory — `App\Dto\AgencyInputFactory`

Le controller `AgenciesController` n'instancie plus directement
`AgencyInput::fromArray()` — il injecte un `AgencyInputFactoryInterface`.
On remplace l'alias d'Aurora par le nôtre :

```php
// aurora-client : src/Dto/AgencyInputFactory.php
namespace App\Dto;

use Aurora\Core\Agency\Dto\AgencyInputFactoryInterface;
use Aurora\Core\Agency\Dto\AgencyInputInterface;
use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AgencyInputFactoryInterface::class)]
class AgencyInputFactory implements AgencyInputFactoryInterface
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

`#[AsAlias(AgencyInputFactoryInterface::class)]` écrase l'alias d'Aurora-core
sur ce même service-id : à la compilation du conteneur, `App\Dto\AgencyInputFactory`
gagne, le controller reçoit votre factory.

### 2.3 Enregistrement — `config/services.yaml`

Le scaffold `bin/create-client` ajoute déjà ces blocs. Sinon :

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\Controller\:
        resource: '../src/Controller/'
        tags: ['controller.service_arguments']

    App\Entity\:
        resource: '../src/Entity/'

    App\Dto\:
        resource: '../src/Dto/'

    App\Manager\:
        resource: '../src/Manager/'

    App\Serializer\:
        resource: '../src/Serializer/'
```

---

## 3. Manager — `App\Manager\AgencyManager`

`resolve_target_entities` n'agit que sur la résolution Doctrine (associations,
queries) — un `new Agency()` PHP littéral instancie toujours la classe importée.
Aurora's `AgencyManager` expose donc deux hooks `protected` :

- `createAgency(): AgencyInterface` — instancie la nouvelle entité
- `applyInput(AgencyInterface $agency, AgencyInputInterface $input): void` — la peuple

Vous override l'un ou l'autre (ou les deux) ; `parent::create()` et
`parent::update()` continuent de gérer persist + audit log.

```php
// aurora-client : src/Manager/AgencyManager.php
namespace App\Manager;

use App\Dto\AgencyInput;
use App\Entity\Agency;
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

        if ($input instanceof AgencyInput && $agency instanceof Agency) {
            $agency->setCode($input->code);
        }
    }
}
```

`#[AsAlias(AgencyManagerInterface::class)]` remplace l'alias d'Aurora ;
le controller injecte votre Manager, qui instancie `App\Entity\Agency`
(via `createAgency`) et persiste dans `client_agencies` avec le bon `code`.

---

## 4. Serializer — `App\Serializer\AgencySerializer`

```php
// aurora-client : src/Serializer/AgencySerializer.php
namespace App\Serializer;

use App\Entity\Agency;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Agency\Serializer\AgencySerializer as AuroraAgencySerializer;
use Aurora\Core\Agency\Serializer\AgencySerializerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AgencySerializerInterface::class)]
class AgencySerializer extends AuroraAgencySerializer
{
    public function serialize(AgencyInterface $agency): array
    {
        $data = parent::serialize($agency);

        if ($agency instanceof Agency) {
            $data['code'] = $agency->getCode();
        }

        return $data;
    }
}
```

À ce stade, le payload JSON renvoyé par `/backend/agencies` contient `code`.

---

## 5. Vue — wrapper avec slots scoped

### 5.1 Composant client — chemin et alias

Aurora expose **deux** globs côté Vue (cf. `vendor/aurora/assets/app.js`) :

- `@client/Module/<Name>/**/*.vue` — vraies features client, exposées comme
  `<name>/<rest>` dans `vue_component()` (ex: `tracking/backend/dashboard/...`)
- `@client/Overrides/**/*.vue` — wrappers autour des composants Aurora,
  exposés sans préfixe module : `<rest>` (ex: `backend/agencies/AgenciesApp`)

Le wrapper Agency est un **override** (il enveloppe `AuroraAgenciesApp`),
pas une feature métier — donc il va sous `Overrides/`.

```vue
<!-- aurora-client : assets/client/Overrides/backend/agencies/AgenciesApp.vue -->
<script setup>
import AuroraAgenciesApp from "@core/backend/agencies/AgenciesApp.vue";
import AppInput from "@/shared/components/form/AppInput.vue";

defineProps({
    agencies: { type: Array, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

// Dit au composable Aurora useAgenciesEdit comment hydrater editForm.code
// (reset à '' en création, lecture depuis agency.code en édition).
const extraFields = {
    code: {
        default: "",
        fromEntity: (agency) => agency.code ?? "",
    },
};
</script>

<template>
    <AuroraAgenciesApp
        :agencies="agencies"
        :create-path="createPath"
        :update-path="updatePath"
        :delete-path="deletePath"
        :extra-fields="extraFields"
    >
        <template #extra-headers>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">Code</th>
        </template>
        <template #extra-cells="{ agency }">
            <td class="px-4 py-3 text-muted">{{ agency.code ?? '—' }}</td>
        </template>
        <template #extra-form-fields="{ editForm, errors }">
            <AppInput
                v-model="editForm.code"
                label="Code"
                placeholder="ex: PARIS-01"
                :error="errors.code ?? ''"
            />
        </template>
    </AuroraAgenciesApp>
</template>
```

Aliases utilisés (déclarés dans `vendor/aurora/vite.config.js`) :

| Alias | Pointe vers |
|---|---|
| `@core` | `vendor/aurora/assets/Core` |
| `@` | `vendor/aurora/assets` (composants `shared/`, etc.) |
| `@client` | `aurora-client/assets/client` (votre dossier) |

Le composable Aurora `useAgenciesEdit(extraFields)` :
- au reset (création) : `editForm.code = ''`
- à l'ouverture en édition : `editForm.code = agency.code`
- à la soumission : `request(url, { ...editForm })` envoie `name` ET `code`

### 5.2 Override du template Twig

Aurora-core auto-prepend `kernel.project_dir/templates/Core/` devant son propre
chemin sous le namespace `@Core`. Mettre votre fichier ici suffit à override —
pas de config Twig supplémentaire.

```twig
{# aurora-client : templates/Core/backend/agencies/index.html.twig #}
{% extends '@Core/backend/layout.html.twig' %}

{% block title %}{{ 'backend.nav.agencies'|trans }} - {{ parent() }}{% endblock %}

{% block page_header_slot %}
    {{ include('@Shared/components/page_header.html.twig', {
        crumbs: [{label: 'backend.nav.agencies'|trans}],
    }) }}
{% endblock %}

{% block body %}
<div {{ vue_component('backend/agencies/AgenciesApp', {
    agencies: agencies,
    createPath: path('backend_agencies_create'),
    updatePath: path('backend_agencies_update', {id: '__id__'}),
    deletePath: path('backend_agencies_delete', {id: '__id__'}),
}) }} class="flex-1 min-w-0"></div>
{% endblock %}
```

La seule ligne qui change vs le template Aurora :
`vue_component('core/backend/agencies/AgenciesApp', …)` →
`vue_component('backend/agencies/AgenciesApp', …)`.

Le préfixe `core/` (Aurora) tombe car le wrapper est exposé sans préfixe par
le glob `@client/Overrides/**/*.vue`.

Vérifier que l'override est bien pris :

```bash
php bin/console debug:twig "@Core/backend/agencies/index.html.twig"
# Matched File doit pointer vers templates/Core/backend/agencies/index.html.twig
```

---

## 6. Tester

```bash
make demo                  # recharge fixtures + sync menus/privileges
make start                 # PHP server + Vite dev server
# → ouvrir /backend/agencies, créer une agence avec un code, recharger, éditer
```

---

## Récap des points d'extension exposés par Aurora pour Agency

| Couche | Interface / point d'extension côté Aurora | Pattern client |
|---|---|---|
| Entité | `AgencyInterface` + `AbstractAgency` | `extends AbstractAgency`, table dédiée |
| ResolveTargetEntity | mapping interface → concrete dans `doctrine.yaml` | `App\Entity\Agency` |
| DTO d'entrée | `AgencyInputInterface` | `extends AgencyInput` |
| Factory de DTO | `AgencyInputFactoryInterface` | `#[AsAlias]` + nouvelle factory |
| Manager | `AgencyManagerInterface` + hooks `protected` (`createAgency()`, `applyInput()`) | `#[AsAlias]` + override des hooks |
| Serializer | `AgencySerializerInterface` | `#[AsAlias]` + `extends AgencySerializer` |
| Validation | Attributs `#[Assert\*]` sur le DTO étendu | Native Symfony Validator |
| Vue table | Slots `extra-headers`, `extra-cells` (scoped sur `agency`) | `<template #extra-cells="{ agency }">` |
| Vue formulaire | Slot `extra-form-fields` (scoped sur `editForm`, `errors`) | `<template #extra-form-fields="{ editForm, errors }">` |
| Vue submit | Prop `extraFields` du composable `useAgenciesEdit` | `{ <field>: { default, fromEntity } }` |
| Template Twig | Aurora prepend `kernel.project_dir/templates/Core/` au namespace `@Core` | Drop file at `templates/Core/<path>.html.twig` |

---

## Limitations connues

1. **Seul Agency** est instrumenté en pilote. Les 46 autres entités Aurora
   sont substituables côté DB (`resolve_target_entities`) mais leurs DTO,
   Manager, Serializer, View et templates restent à ouvrir un par un.
2. **La table `core_agencies`** reste mappée à l'entité Aurora et créée par la
   migration baseline d'Aurora, même si plus rien ne pointe dessus. C'est du
   "dead weight" — pas grave fonctionnellement, on pourra plus tard supprimer
   automatiquement la déclaration `#[ORM\Entity]` sur Aurora's concrete quand
   un client la remplace.
