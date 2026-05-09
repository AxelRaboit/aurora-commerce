# Piège : nouveau module → **2 registrations** obligatoires pour les traductions

## Règle

Quand tu crées un nouveau module avec un dossier `src/Module/<Module>/translations/`,
il faut l'enregistrer **deux fois** (plus besoin de toucher services.yaml ni app.js) :

1. **Serveur (Twig + console)** : `src/AuroraBundle.php`
   ```php
   $builder->prependExtensionConfig('framework', [
       'translator' => [
           'paths' => [
               // ...
               $dir.'/src/Module/<Module>/translations',
           ],
       ],
   ]);
   ```

2. **Frontend (vue-i18n)** : `src/Core/Setting/Command/DumpJsTranslationsCommand.php`
   ```php
   private const array AURORA_SOURCE_DIRS = [
       // ...
       'src/Module/<Module>/translations',
   ];
   ```

## Ce qui est automatique (ne pas toucher)

- **`config/services.yaml`** : `ModuleInterface` est dans `_instanceof → tags: [aurora.module]`.
  Toute classe implémentant `ModuleInterface` est auto-taggée → sidebar + permissions OK.
- **`assets/app.js`** : le glob `./Module/**/*.vue` couvre automatiquement tout nouveau module.
  Les composants Vue sont exposés comme `vue_component('<module>/backend/Foo')` sans ligne à ajouter.

## Pourquoi 2 pipelines séparés

- **Twig + console** lisent via Symfony Translator (`framework.translator.paths` dans `AuroraBundle.php`).
- **vue-i18n** lit `assets/locales/generated/{fr,en}.json`, généré par `app:translations:dump-js`
  qui a sa propre liste hardcodée `AURORA_SOURCE_DIRS`.

## Comment vérifier

```bash
# Vérifier les 2 enregistrements de traductions
grep -n "src/Module/<Module>/translations" \
    src/AuroraBundle.php \
    src/Core/Setting/Command/DumpJsTranslationsCommand.php
# Doit retourner UNE ligne dans CHACUN des 2 fichiers.

# Régénérer et rebuilder
make i18n && npm run build
```

## Détection

Si sur une page Vue tu vois des clés brutes (`backend.plannings.title`) :

```bash
grep -c "<feature>" assets/locales/generated/fr.json
# 0 → la 2e registration manque (DumpJsTranslationsCommand)
```
