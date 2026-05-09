# Pattern : étendre une entité Aurora avec un champ custom

## Règle

Pour ajouter un champ à une entité Aurora (ex: `code` sur `Agency`), 4 étapes :

1. Créer `App\Entity\<Name>` qui étend `Aurora\…\Abstract<Name>` et
   `implements <Name>Interface`.
2. Ajouter les colonnes Doctrine + getters/setters pour les champs custom.
3. Inscrire dans `App\AuroraBundle::$resolve_target_entities`.
4. Migration Doctrine.

## Pourquoi

- L'**Interface** Aurora reste stable (pas modifiée).
- L'**Abstract** Aurora apporte les colonnes communes (MappedSuperclass).
- La concrete client a son propre `id` + sequence + champs custom.
- `resolve_target_entities` route les relations Doctrine vers la classe
  client (mais pas les `new` directs — cf
  `pitfall_create_hook_required.md`).

## Comment l'appliquer

### 1. Entité concrète client

```php
namespace App\Entity;

use Aurora\Core\Agency\Entity\AbstractAgency;
use Aurora\Core\Agency\Entity\AgencyInterface;
use App\Repository\AppAgencyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppAgencyRepository::class)]
#[ORM\Table(name: 'client_agencies')]
class Agency extends AbstractAgency implements AgencyInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    #[ORM\SequenceGenerator(sequenceName: 'seq_client_agency_id', initialValue: 1)]
    protected ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    protected ?string $code = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;
        return $this;
    }
}
```

**Notes** :
- Sequence client préfixée `seq_client_*` (ou autre namespace) pour éviter
  les collisions avec `seq_core_*` Aurora.
- Table `client_agencies` (préfixe différent de `core_agencies` Aurora).
- `protected` sur les propriétés pour que le client puisse étendre encore
  (rare mais possible).

### 2. resolve_target_entities

```php
// src/AuroraBundle.php
class AuroraBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'resolve_target_entities' => [
                    \Aurora\Core\Agency\Entity\AgencyInterface::class => \App\Entity\Agency::class,
                    // … autres entités étendues
                ],
            ],
        ]);
    }
}
```

### 3. Migration

```bash
php bin/console doctrine:migrations:diff
# Vérifier la migration générée
php bin/console doctrine:migrations:migrate
```

## Étapes suivantes

- Pour étendre le DTO d'entrée : [pattern_extend_dto.md](pattern_extend_dto.md)
- Pour étendre le Manager (et faire instancier la classe client) :
  [pattern_extend_manager.md](pattern_extend_manager.md)
- Pour étendre le Serializer : [pattern_extend_serializer.md](pattern_extend_serializer.md)
- Pour étendre la Vue : [pattern_extend_vue.md](pattern_extend_vue.md)

Vue d'ensemble : [checklist_extend_full_entity.md](checklist_extend_full_entity.md).
