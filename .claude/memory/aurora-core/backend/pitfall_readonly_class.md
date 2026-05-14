# Piège : `readonly class` ≠ `class { public readonly … }`

## Règle

Pour un DTO Aurora extensible, **toujours** utiliser :
```php
class <Name>Input implements <Name>InputInterface
{
    public function __construct(
        public readonly string $name = '',
        // …
    ) {}
}
```

**Jamais** :
```php
readonly class <Name>Input implements <Name>InputInterface  // ❌
{
    public function __construct(
        public string $name = '',
        // …
    ) {}
}
```

## Pourquoi

Subtil mais important : `readonly class` (PHP 8.2+, mot-clé global) **force
toute classe enfant à être également `readonly class`**. Un client qui
étend ne peut donc plus ajouter de propriété **mutable** (compteur, cache
interne, etc.) :

```php
// Si parent est `readonly class`
class App\AgencyInput extends \Aurora\…\AgencyInput
{
    public string $cachedSlug;  // ❌ ERROR: must be readonly
}

// Avec parent en `class { public readonly }`
class App\AgencyInput extends \Aurora\…\AgencyInput
{
    public string $cachedSlug;  // ✅ OK
    public readonly string $code;  // ✅ aussi OK
}
```

## Comment l'appliquer

### Au moment d'écrire un nouveau DTO

```php
class FoobarInput implements FoobarInputInterface
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $name = '',
        public readonly ?string $description = null,
    ) {}

    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
}
```

Note : la classe elle-même est **non-`final`**, **non-`readonly`** au niveau
classe ; chaque propriété est **`public readonly`** individuellement.

### Pour les sub-DTOs (consommés en interne)

Eux peuvent rester `final readonly class` car ils ne sont pas étendus :
```php
final readonly class PostTranslationInput
{
    public function __construct(
        public string $title,
        public string $slug,
        // …
    ) {}
}
```

## Source

Découvert lors de l'audit post-Editorial (commit `5d3643d` qui a refactoré
les 4 anciens DTOs Agency/Deal/Checkout/Post + les 2 User pour aligner sur
le bon style).
