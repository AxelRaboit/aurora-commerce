# Piège : nouveau module → **3 registrations** obligatoires

## Règle

Quand tu crées un nouveau module avec un dossier `src/Module/<Module>/translations/`,
il faut l'enregistrer **trois fois** :

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

3. **Sidebar / permissions** : `config/services.yaml`
   ```yaml
   App\Module\<Module>\<ModuleClass>:
       tags:
           - { name: aurora.module }
   ```
   Sans ce tag, le module n'apparaît pas dans la sidebar admin et ses permissions
   ne sont pas reconnues par `ModulePermissionVoter`.

## Pourquoi

Les deux pipelines lisent les fichiers `messages.fr.yaml` / `messages.en.yaml`
mais via des mécanismes **séparés** :

- **Twig + console** lisent via le composant Symfony Translator, configuré par
  `framework.translator.paths` dans `AuroraBundle.php`. Si non enregistré : les
  templates Twig affichent la clé brute (`backend.plannings.title`).

- **vue-i18n** lit `assets/locales/generated/{fr,en}.json`, généré par la
  commande `app:translations:dump-js`. Cette commande a sa propre liste
  hardcodée `AURORA_SOURCE_DIRS`. Si non enregistré : `make i18n` produit le
  JSON sans les clés du nouveau module → la Vue affiche les clés brutes.

C'est facile d'oublier la 2e registration parce que tout marche côté Twig dès
qu'on enregistre la 1re. La régression apparaît seulement quand on ouvre la
page Vue.

## Comment l'appliquer

À chaque création de module qui ajoute des traductions :

```bash
# 1. Vérifier les 2 fichiers de traductions
grep -n "src/Module/<Module>/translations" \
    src/AuroraBundle.php \
    src/Core/Setting/Command/DumpJsTranslationsCommand.php

# Doit retourner UNE ligne dans CHACUN des 2 fichiers.
# Si manquant : ajouter à l'endroit qui manque.

# 2. Vérifier le tag aurora.module dans services.yaml
grep -n "aurora.module" config/services.yaml
# Doit contenir une entrée pour le nouveau module.

# 2. Lancer make i18n + rebuild
make i18n
npm run build
```

## Détection

Si en navigant sur `/backend/<feature>` tu vois des clés brutes type
`backend.plannings.title` au lieu de "Plannings" :

```bash
grep -c "<feature>" assets/locales/generated/fr.json
# 0 → la 2e registration manque (DumpJsTranslationsCommand)
```

## Source

Détecté le 8 mai 2026 sur le module Planning. Initialement seules les
clés Twig étaient enregistrées dans `AuroraBundle.php`, le Vue affichait
les clés brutes. Fix dans le commit
`i18n(planning): register Planning translations in dump-js command`.

## Piste d'amélioration future

`AURORA_SOURCE_DIRS` pourrait être auto-découvert (scanner tous les
`src/Module/*/translations/`) au lieu d'être hardcodé. Demanderait une
PR séparée — pour l'instant, suivre la convention manuelle.
