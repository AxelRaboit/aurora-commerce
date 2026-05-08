# Piège : oublier d'override `create<X>()` côté client

## Symptôme

Tu as :
- Étendu `Agency` en `App\Entity\Agency` avec un champ `code`.
- Étendu `AgencyInput` en `App\Dto\AgencyInput` avec `code`.
- Décoré la `AgencyInputFactory`.
- Override `applyInput()` pour copier `code` du DTO vers l'entité.

Tu crées une nouvelle agence depuis l'admin. Le formulaire envoie `code`,
le DTO le reçoit, mais... après création, `code` est `null` en base et
l'entité Doctrine n'est pas un `App\Entity\Agency` mais un `Aurora\…\Agency`.

## Cause

Le Manager Aurora fait `new \Aurora\…\Agency()` directement (sans hook
`createAgency()` override par toi). Doctrine persiste cette classe Aurora
qui n'a pas la colonne `code`. Ton `applyInput()` essaie d'appeler
`$agency->setCode(…)` mais `Aurora\…\Agency` n'a pas cette méthode → soit
silent ignore (si typed weakly), soit erreur fatale.

## Règle

**Toujours** override `create<X>()` quand on étend une entité, même si on
ne change rien d'autre :

```php
class AgencyManager extends BaseAgencyManager
{
    protected function createAgency(): AgencyInterface
    {
        return new \App\Entity\Agency();  // ✅ classe client
    }
}
```

## Pourquoi (rappel)

`resolve_target_entities` ne s'applique qu'aux **relations Doctrine**, pas
aux `new` directs dans le code. Le hook `create<X>()` est l'équivalent
runtime de `resolve_target_entities` pour les instanciations directes.

Cf [pitfall_resolve_target_entities.md](../pitfall_resolve_target_entities.md)
côté core pour le contexte complet.

## Vérification

Après extension, vérifier :

```bash
# Le service AgencyManagerInterface doit pointer vers AppAgencyManager
php bin/console debug:container Aurora\\Core\\Agency\\Manager\\AgencyManagerInterface
```

Et faire un test :

```php
$agency = $manager->create(new App\Dto\AgencyInput(name: 'Test', code: 'X'));
self::assertInstanceOf(App\Entity\Agency::class, $agency);  // doit passer
self::assertSame('X', $agency->getCode());  // doit passer
```
