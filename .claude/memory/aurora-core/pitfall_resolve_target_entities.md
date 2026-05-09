# Piège : `resolve_target_entities` ne couvre pas `new`

## Règle

Le mécanisme Doctrine `resolve_target_entities` **ne s'applique qu'aux
relations Doctrine** (`@ManyToOne`, `@OneToMany`, etc.). Il **ne fait
rien** sur :
- Les `new <Name>()` directs dans le code
- Les requêtes (`$em->getRepository(<Name>::class)` doit utiliser le bon
  resolver)

## Pourquoi

C'est exactement la raison pour laquelle le hook `protected create<X>():
<X>Interface` existe. Sans lui, un client qui a étendu `Agency` :

```php
// Manager Aurora-core
public function create(AgencyInputInterface $input): AgencyInterface
{
    $agency = new Agency();  // ❌ Toujours la classe Aurora, pas App\Agency
    $this->applyInput($agency, $input);
    // …
}
```

→ Doctrine persiste la classe Aurora, **pas** la classe étendue. Les
champs custom du client sont perdus.

## Comment l'appliquer

### Au moment d'écrire un Manager

Lister toutes les classes que le Manager instancie via `new`. Pour chaque,
créer un hook :

```php
public function create(AgencyInputInterface $input): AgencyInterface
{
    $agency = $this->createAgency();  // ✅ via hook
    $this->applyInput($agency, $input);
    // …
}

protected function createAgency(): AgencyInterface
{
    return new Agency();  // override-able par le client
}
```

### Côté client

Override le hook + déclarer dans `resolve_target_entities` (pour les
relations Doctrine — c'est complémentaire) :

```php
// App\Manager\AppAgencyManager
class AppAgencyManager extends \Aurora\…\AgencyManager
{
    protected function createAgency(): AgencyInterface
    {
        return new \App\Entity\Agency();  // classe étendue
    }
}

// App\AuroraBundle (pour les relations qui pointent vers Agency)
'resolve_target_entities' => [
    \Aurora\…\AgencyInterface::class => \App\Entity\Agency::class,
];
```

Les **deux mécanismes sont nécessaires** :
- `createAgency()` hook → couvre les `new Agency()` directs dans les
  Managers.
- `resolve_target_entities` → couvre les relations Doctrine (`@ManyToOne(targetEntity: AgencyInterface::class)`).

## Source

Découvert au début du rollout (Agency pilot). Cf le doc convention,
section "Couche bonus — ResolveTargetEntityRepository" et la note de la
section 3.1 sur les hooks d'instanciation.
