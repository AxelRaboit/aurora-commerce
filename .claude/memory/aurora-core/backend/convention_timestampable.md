# Convention Timestampable Aurora

## Règle

Utiliser exclusivement `Aurora\Core\Timestampable\TimestampableTrait` et
`Aurora\Core\Timestampable\TimestampableInterface`. Ne jamais utiliser
`Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait` ni
`Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface`.

**Namespace** : `Aurora\Core\Timestampable\` (dossier `src/Core/Timestampable/`).

**Différences clés vs KNP** :
- Propriétés `private DateTimeImmutable` (typées, non-nullable)
- Getters retournent `DateTimeImmutable` (pas `?DateTimeInterface`)
- Pas de setters `setCreatedAt()` / `setUpdatedAt()` / `updateTimestamps()`
- Colonnes ORM déclarées dans le trait (`#[ORM\Column]`)
- Lifecycle via `#[ORM\PrePersist]` / `#[ORM\PreUpdate]` dans le trait

**Obligation sur les classes Abstract** : ajouter `#[ORM\HasLifecycleCallbacks]`
sur chaque `MappedSuperclass` qui utilise le trait, sinon les callbacks
`setCreatedAtValue` / `setUpdatedAtValue` ne se déclenchent pas.

## Pourquoi

KNP gère les timestamps via un subscriber externe (couplage implicite). Aurora
utilise des lifecycle callbacks déclarés dans le trait, ce qui est auto-contenu,
plus lisible et indépendant de la lib KNP.

## Comment l'appliquer

```php
use Aurora\Core\Timestampable\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]          // obligatoire
abstract class AbstractFoo implements FooInterface
{
    use TimestampableTrait;
    // ...
}
```

Interface :
```php
use Aurora\Core\Timestampable\TimestampableInterface;

interface FooInterface extends TimestampableInterface { ... }
```

**Supprimer `updateTimestamps()`** : avec Aurora, la méthode n'existe pas.
Pour forcer Doctrine à marquer une entité dirty (ex : bump `@Version`),
utiliser `$entityManager->getUnitOfWork()->scheduleForUpdate($entity)`.
