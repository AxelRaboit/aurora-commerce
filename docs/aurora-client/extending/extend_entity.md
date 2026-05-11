# Étendre une entité Aurora

Aurora expose un pattern d'extensibilité en 5 couches pour chaque entité
avec page backend CRUD. Ce guide suit l'exemple de `Agency` (déjà implémenté
dans le projet) qui ajoute un champ `code`.

Pour le détail complet du pattern côté aurora-core, voir
[`../aurora-core/dev/entity_extensibility_convention.md`](../aurora-core/dev/entity_extensibility_convention.md).
Ce guide-ci se concentre sur **ce que le dev client doit écrire**.

---

## Vue d'ensemble des 5 couches

| Couche | Fichier client | Ce qu'on override |
|---|---|---|
| 1. Entité | `src/Entity/Agency.php` | Ajoute colonnes, déclare la table client |
| 2. DTO | `src/Dto/AgencyInput.php` + `AgencyInputFactory.php` | Ajoute propriétés + parsing |
| 3. Manager | `src/Manager/AgencyManager.php` | Hook `createAgency()` + `applyInput()` |
| 4. Serializer | `src/Serializer/AgencySerializer.php` | Ajoute champs dans le payload JSON |
| 5. Vue | `assets/client/Overrides/backend/agencies/AgenciesApp.vue` | Slots `extra-*` |

---

## Couche 1 — Entité

Étendre `Abstract<Name>` (MappedSuperclass Aurora), déclarer une nouvelle table,
ajouter sa propre séquence PK.

```php
// src/Entity/Agency.php
namespace App\Entity;

use Aurora\Core\Agency\Entity\AbstractAgency;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
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

Déclarer la substitution dans `config/packages/doctrine.yaml` :

```yaml
doctrine:
    orm:
        resolve_target_entities:
            Aurora\Core\Agency\Entity\AgencyInterface: App\Entity\Agency
```

Générer la migration :

```bash
make migration   # génère la migration
make migrate     # l'applique
```

---

## Couche 2 — DTO

Étendre `<Name>Input` pour ajouter la nouvelle propriété, et la factory pour
parser le champ depuis le tableau de données.

```php
// src/Dto/AgencyInput.php
namespace App\Dto;

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
}
```

```php
// src/Dto/AgencyInputFactory.php
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

> Le `#[AsAlias(AgencyInputFactoryInterface::class)]` est ce qui fait que
> le controller Aurora utilisera automatiquement cette factory au lieu de
> la factory par défaut.

---

## Couche 3 — Manager

Surcharger `createAgency()` pour instancier l'entité cliente, et `applyInput()`
pour hydrater le champ supplémentaire.

```php
// src/Manager/AgencyManager.php
namespace App\Manager;

use App\Entity\Agency;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Agency\Manager\AgencyManager as AuroraAgencyManager;
use Aurora\Core\Agency\Manager\AgencyManagerInterface;
use Aurora\Core\Agency\Dto\AgencyInputInterface;
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

        if ($agency instanceof Agency && $input instanceof \App\Dto\AgencyInput) {
            $agency->setCode($input->code);
        }
    }
}
```

> Toujours appeler `parent::applyInput()` **en premier** — sinon les champs
> Aurora (ex: `name`) ne sont pas hydratés.

---

## Couche 4 — Serializer

Surcharger `serialize()` pour ajouter le champ au payload JSON renvoyé au front.

```php
// src/Serializer/AgencySerializer.php
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
        return [
            ...parent::serialize($agency),
            'code' => $agency instanceof Agency ? $agency->getCode() : null,
        ];
    }
}
```

---

## Couche 5 — Vue

Créer un composant qui étend le composant Aurora via la prop `extraFields`
et les slots scoped.

```
assets/client/Overrides/backend/agencies/AgenciesApp.vue
```

```vue
<template>
  <!-- Import du composant Aurora -->
  <AuroraAgenciesApp :extra-fields="extraFields">
    <!-- Colonne supplémentaire dans le tableau -->
    <template #extra-headers>
      <th>Code</th>
    </template>

    <!-- Cellule pour chaque ligne -->
    <template #extra-cells="{ agency }">
      <td>{{ agency.code ?? '—' }}</td>
    </template>

    <!-- Champ dans le formulaire create/edit -->
    <template #extra-form-fields="{ editForm, errors }">
      <AppInput
        v-model="editForm.code"
        label="Code"
        placeholder="Ex: AGC-001"
        :error="errors.code"
      />
    </template>
  </AuroraAgenciesApp>
</template>

<script setup>
import AuroraAgenciesApp from '@core/backend/agencies/AgenciesApp.vue';
import AppInput from '@shared/components/AppInput.vue';

const extraFields = {
  code: {
    default: '',
    fromEntity: (agency) => agency.code ?? '',
  },
};
</script>
```

Créer le template Twig qui charge ce composant à la place de celui d'Aurora :

```twig
{# templates/Core/backend/agencies/index.html.twig #}
{% extends '@Core/backend/layout.html.twig' %}

{% block content %}
    {{ vue_component('core/backend/agencies/AgenciesApp') }}
{% endblock %}
```

> Aurora détecte automatiquement ce template en priorité grâce au prepend
> de `templates/` dans les chemins Twig. Aucune configuration supplémentaire.

---

## Déclarer la substitution dans services.yaml

Vérifier que les services PSR-4 sont enregistrés dans `config/services.yaml` :

```yaml
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

## Vérifier que ça fonctionne

```bash
# L'alias pointe sur la bonne classe ?
make sf CMD="debug:container Aurora\Core\Agency\Manager\AgencyManagerInterface"
# Doit afficher : App\Manager\AgencyManager

make sf CMD="debug:container Aurora\Core\Agency\Dto\AgencyInputFactoryInterface"
# Doit afficher : App\Dto\AgencyInputFactory

make cc       # vider le cache si besoin
make ft       # tests verts
```

---

## Référence : préfixes de séquences réservés

Ne jamais utiliser les préfixes Aurora dans les séquences client.
Voir la liste complète dans
[`../aurora-core/dev/extending_aurora.md`](../aurora-core/dev/extending_aurora.md)
(section "Préfixes réservés").

Convention pour les préfixes client : ≥ 4 caractères, suffixe projet,
ex: `CTRX`, `INTX`, `TRKP`.
