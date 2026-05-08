# Étendre Agency de bout en bout (pilote Niveau 2)

Ce guide montre comment **ajouter un champ `code`** à l'entité `Agency` côté
aurora-client, **avec affichage dans le tableau backoffice et saisie dans le
formulaire de création/édition**, sans dupliquer le code Aurora.

C'est le **pilote** du pattern d'extensibilité complet (Sylius-style) qui sera
ensuite généralisé aux autres entités. Il s'appuie sur des points d'extension
côté PHP (factory, manager, serializer décorables) et côté Vue (slots nommés).

---

## 1. Étendre l'entité

```php
// aurora-client : src/Entity/Agency.php
namespace App\Entity;

use Aurora\Core\Agency\Entity\Agency as AuroraAgency;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Agency extends AuroraAgency
{
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = null;

    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $code): static { $this->code = $code; return $this; }
}
```

Configurer la substitution Doctrine et générer la migration :

```yaml
# aurora-client : config/packages-custom.yaml
doctrine:
    orm:
        resolve_target_entities:
            Aurora\Core\Agency\Entity\AgencyInterface: App\Entity\Agency
```

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

---

## 2. Étendre le DTO d'entrée

```php
// aurora-client : src/DTO/AgencyInput.php
namespace App\DTO;

use Aurora\Core\Agency\DTO\AgencyInput as AuroraAgencyInput;
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

Décorer la factory pour qu'Aurora utilise votre DTO :

```php
// aurora-client : src/DTO/AgencyInputFactory.php
namespace App\DTO;

use Aurora\Core\Agency\DTO\AgencyInputFactoryInterface;
use Aurora\Core\Agency\DTO\AgencyInputInterface;
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

`#[AsAlias(AgencyInputFactoryInterface::class)]` remplace l'alias Aurora par
votre service. Le controller Aurora continue de type-hint l'interface et
reçoit votre factory automatiquement.

---

## 3. Étendre le Manager

```php
// aurora-client : src/Manager/AgencyManager.php
namespace App\Manager;

use App\DTO\AgencyInput;
use App\Entity\Agency;
use Aurora\Core\Agency\DTO\AgencyInputInterface;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Agency\Manager\AgencyManager as AuroraAgencyManager;
use Aurora\Core\Agency\Manager\AgencyManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AgencyManagerInterface::class)]
class AgencyManager extends AuroraAgencyManager
{
    public function create(AgencyInputInterface $input): AgencyInterface
    {
        $agency = parent::create($input);

        if ($input instanceof AgencyInput && $agency instanceof Agency) {
            $agency->setCode($input->code);
            $this->entityManager->flush();
        }

        return $agency;
    }

    public function update(AgencyInterface $agency, AgencyInputInterface $input): void
    {
        parent::update($agency, $input);

        if ($input instanceof AgencyInput && $agency instanceof Agency) {
            $agency->setCode($input->code);
            $this->entityManager->flush();
        }
    }
}
```

---

## 4. Étendre le Serializer

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

À ce stade, le backend reçoit, valide, persiste et renvoie `code` — il ne
manque que le rendu côté Vue.

---

## 5. Étendre la page Vue

### 5.1 Composant wrapper côté client

```vue
<!-- aurora-client : assets/backend/agencies/AppAgenciesApp.vue -->
<script setup>
import AuroraAgenciesApp from "@aurora/Core/backend/agencies/AgenciesApp.vue";
import AppInput from "@/shared/components/form/AppInput.vue";

defineProps({
    agencies: { type: Array, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

// La config qui décrit comment hydrater editForm.code
const extraFields = {
    code: {
        default: "",
        fromAgency: (agency) => agency.code ?? "",
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

Le composable Aurora `useAgenciesEdit` lit la config `extraFields` :
- au reset (création), `editForm.code = ''`
- à l'ouverture en édition, `editForm.code = agency.code`
- à la soumission, `request(url, { ...editForm })` envoie automatiquement
  `name` ET `code` au backend

### 5.2 Override du template Twig

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
<div {{ vue_component('backend/agencies/AppAgenciesApp', {
    agencies: agencies,
    createPath: path('backend_agencies_create'),
    updatePath: path('backend_agencies_update', {id: '__id__'}),
    deletePath: path('backend_agencies_delete', {id: '__id__'}),
}) }} class="flex-1 min-w-0"></div>
{% endblock %}
```

La seule différence : `vue_component('core/backend/agencies/AgenciesApp', ...)`
devient `vue_component('backend/agencies/AppAgenciesApp', ...)`. Symfony charge
votre template avant celui d'Aurora car il est dans `templates/Core/...`.

---

## Récap des points d'extension exposés par Aurora

| Couche | Interface / point d'extension | Pattern client |
|---|---|---|
| Entité | `AgencyInterface` + `AbstractAgency` | `extends Agency` (option A) ou `extends AbstractAgency` (option B) |
| DTO | `AgencyInputInterface` | `extends AgencyInput` ou implémentation custom |
| Factory | `AgencyInputFactoryInterface` | `#[AsAlias]` (remplace) ou `#[AsDecorator]` |
| Manager | `AgencyManagerInterface` | `#[AsAlias]` + `extends AgencyManager` |
| Serializer | `AgencySerializerInterface` | `#[AsAlias]` + `extends AgencySerializer` |
| Validation | Attributs `#[Assert\*]` sur les propriétés du DTO étendu | Native (Symfony Validator les trouve via réflexion) |
| Vue table | Slot `extra-headers`, `extra-cells` (scoped sur `agency`) | `<template #extra-cells="{ agency }">` |
| Vue formulaire | Slot `extra-form-fields` (scoped sur `editForm`, `errors`) | `<template #extra-form-fields="{ editForm, errors }">` |
| Vue submit | Prop `extraFields` du composable `useAgenciesEdit` | Décrit `default` + `fromAgency` par champ |
| Template Twig | Override par chemin `templates/Core/backend/agencies/index.html.twig` | Symfony charge le client en priorité |
