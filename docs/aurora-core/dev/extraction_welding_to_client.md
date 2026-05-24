# Extraction Welding → aurora-welding (client) — mai 2026

Trace de la décision et de la procédure d'extraction du module `Welding`
d'aurora-core vers le projet client `aurora-welding`.

## Pourquoi

Le module Welding (workflows de soudure réglementée + PDF templates +
signature canvas + audit trail) avait été développé dans aurora-core
en partant de l'idée qu'il serait *un module dont les clients auraient
de fait besoin d'étendre*. À l'usage, il s'est avéré spécifique à un
seul secteur métier (industriel nucléaire) et pollue aurora-core
(JS deps `pdf-lib`/`pdfjs-dist`, scheduler welding-specific, sequence
prefix, toggle dashboard) pour les autres clients qui n'en ont pas
besoin.

Décision : extraire Welding entièrement hors core, dans le projet
client `aurora-welding/` (dupliqué d'aurora-client). aurora-core
redevient agnostique du métier soudure.

## Procédure d'extraction (5 phases)

### Phase 0 — Safety net
- Tag `pre-welding-extract` sur le commit `fe3ace49` (juste avant)
- Branche `feat/extract-welding-to-client` sur aurora-core

### Phase 1 — Duplication
- `cp -r aurora-client/ aurora-welding/`, strip `.git`/`vendor/`/etc.
- composer.json : new name (`axelraboit/aurora-welding`), path repo
  vers `../aurora-core` pour le dev (symlink), autoload-dev `App\Tests\`

### Phase 2 — Déplacement Welding
- `src/Module/Welding/` (188 fichiers PHP + Vue + Twig) →
  `aurora-welding/src/Module/Welding/`
- `tests/Unit/Module/Welding/` (24 fichiers) →
  `aurora-welding/tests/Unit/Module/Welding/`
- `tools/pdf/` (fill.mjs + fonts) → `aurora-welding/tools/pdf/`
- 4 migrations Welding + 5 pdfform supprimées (le client génère
  les siennes via `doctrine:migrations:diff` en Phase 4 — tables
  renommées `core_welding_*` → `app_welding_*`)
- Mémoire `pitfall_pdflib_remove_field.md` migrée
- Doc `docs/aurora-core/todo/welding/` migrée
- Rewrite namespaces : `Aurora\Module\Welding` → `App\Module\Welding`
  (175 fichiers, 572 occurrences) + `Aurora\Tests\Unit\Module\Welding`
  → `App\Tests\Unit\Module\Welding`
- Helper de test `CreatesStorageUrlGenerators` copié vers
  `aurora-welding/tests/Concern/`

### Phase 3 — Nettoyage aurora-core
4 touchpoints PHP :
- `src/AuroraBundle.php` : 14 `use` + 14 entries `resolve_target_entities`
  retirées
- `src/Module/Configuration/Setting/Enum/ModuleParameterEnum.php` :
  3 cases (`WeldingBackend`, `WeldingPdfTemplates`, `WeldingPdfDocuments`)
  + labels + descriptions + `getCascadeRequires()` + `getParentCase()`
- `src/Core/Scheduler/MessageHandler/CleanTempFilesHandler.php` :
  toute la logique welding retirée (option a — le handler ne fait plus
  que du SSH temp cleanup)
- `src/Core/Sequence/SequencePrefixEnum.php` : case `WeldingPdfDocument`

Autres nettoyages :
- `package.json` : `pdf-lib` + `pdfjs-dist` retirés
- `aliases.js` : alias `@welding` retiré
- `src/Core/Frontend/backend/sidemenu/composables/useSidemenuSectionTheme.js` :
  thème `pdfform` retiré
- `src/Core/DataFixtures/DemoFixtures.php` : méthode `createPdfForm()`
  + son call site retirés

### Phase 4 — Câblage aurora-welding
- Register `App\Module\Welding\WeldingModule` dans le bundle client
- Étendre `App\AuroraBundle` (config DI) avec les nouveaux
  `resolve_target_entities` Welding
- Adapter `App\ModuleParameterEnum` (si besoin) pour le toggle dashboard
- Twig namespace `@Welding` dans `config/packages/twig.yaml`
- Translations Welding dans `DumpJsTranslationsCommand.$extraSourceDirs`
- Composer install + npm install + `doctrine:migrations:diff` →
  génère la migration `app_welding_*` finale

### Phase 5 — Verification
- aurora-core : tests verts, build OK, schema:validate OK
- aurora-welding : tests verts, build OK, /backend/welding/* répond

## Garde-fou — base de données existante

Si un autre client a déjà appliqué les migrations welding/pdfform
d'aurora-core (avant cette extraction), il aura des tables orphelines
`core_pdfform_*` / `core_welding_*` en base après pull. Ces tables ne
sont plus référencées par aurora-core post-extraction, donc inactives.
Cleanup optionnel manuel via `DROP TABLE` si besoin.

## Si on veut refaire le même schéma demain

Pour extraire un autre module d'aurora-core en client-spécifique, suivre
le même playbook en remplaçant `Welding` par le nom du module à
extraire. Le commit série sur cette branche
`feat/extract-welding-to-client` est la référence canonique.
