# Convention : éditer les YAML sources, jamais les JSON générés

## Règle

**Toujours** ajouter / modifier les traductions dans les **fichiers
sources YAML** :

- `src/Core/translations/messages.{fr,en}.yaml`
- `src/Module/<Module>/translations/messages.{fr,en}.yaml`

**Jamais** modifier directement :

- `assets/locales/generated/fr.json`
- `assets/locales/generated/en.json`

Ces JSON sont **régénérés** par `make i18n` (qui appelle
`app:translations:dump-js`). Toute édition manuelle sera **écrasée** au
prochain dump.

## Workflow correct

```bash
# 1. Éditer les YAML sources
vim src/Module/Planning/translations/messages.fr.yaml
vim src/Module/Planning/translations/messages.en.yaml

# 2. Régénérer les JSON pour vue-i18n
make i18n

# 3. Rebuild les assets pour que le frontend prenne la nouvelle version
npm run build
# ou laisser le dev server le faire automatiquement
```

## Pourquoi

- **Source unique de vérité** : Symfony Translator (Twig + console)
  + vue-i18n (frontend) lisent tous les deux les YAML. Le JSON est juste
  un cache de build pour le bundle JS.
- **Diff lisible** : les YAML sont pluggés dans Git ; les JSON générés
  changent à chaque dump et polluent les diffs.
- **Rollback automatique** : si quelqu'un modifie le JSON pour tester,
  `make i18n` ramène à l'état canonique. Mais perte de l'édition si pas
  reportée.

## Comment détecter une violation

Si un PR contient un diff sur `assets/locales/generated/*.json` SANS
diff correspondant sur `src/.../translations/messages.*.yaml`, il y a
violation.

Le `.gitignore` ne couvre PAS ces JSON (ils sont versionnés pour que
`npm run build` fonctionne sans `make i18n` préalable côté CI). C'est
pourquoi la discipline humaine reste nécessaire.

## Voir aussi

- [`pitfall_module_translations_two_registrations.md`](pitfall_module_translations_two_registrations.md)
  — un nouveau module doit s'enregistrer dans `AuroraBundle.php` ET
  `DumpJsTranslationsCommand` pour que les YAML soient lus.
- [`convention_privilege_translations.md`](convention_privilege_translations.md)
  — où placer les traductions de privilèges par module.

## Source

Convention rappelée par l'utilisateur le 2026-05-09 après ajout des
traductions de privilèges, pour rappel que tout passe par les YAML
sources.
