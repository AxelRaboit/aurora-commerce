---
name: convention-no-cross-module-dep
description: aurora-core modules must never import from sibling modules — cross-module wiring is the client's responsibility via extension points (`extraFields`, `extraEditorTools`, …)
metadata:
  type: feedback
---

**Règle dure** : à l'intérieur d'aurora-core, un module ne dépend **jamais**
d'un autre module sibling. Les seules dépendances permises depuis
`src/Module/X/` ou `src/Module/X/assets/` sont :

- `Core` (`src/Core/`, `src/Core/Frontend/`, `@core/`)
- `Shared` (`src/Core/Frontend/shared/`, `@shared/`)
- ses propres sous-dossiers

Toute dépendance "Editorial → Ecommerce", "Notes → Editorial",
"Billing → Crm", etc. est interdite, **même** si c'est juste un import
JS d'une classe utilitaire.

**Why** : aurora-core est un bundle composable. Un client peut activer
Editorial sans Ecommerce, ou Notes sans Photo. Une dépendance directe
casse cette promesse : le code mort est chargé, le tree-shaking ne
peut rien faire, et désactiver un module fait exploser le build d'un
autre. Découvert sur l'import `ProductGridBlock from @ecommerce/...`
dans `EditorBlock.vue` (Editorial) — l'extraction de l'éditeur en
`@shared` a rendu la fuite visible.

**How to apply** :
1. Quand une feature nécessite de plugger un truc d'un module B dans
   un composant d'un module A, **A expose un point d'extension typé**
   (prop `extraXxx`, slot scoped, hook composable). Pattern identique
   à `extraFields` côté DTO/Vue.
2. **C'est `aurora-client` (ou `App\AuroraBundle` côté projet) qui
   câble** A et B ensemble en passant la prop. aurora-core ne fait
   jamais le câblage lui-même.
3. Côté Vue, l'extension passe par les props du root component
   (ex: `PostsApp` → `extra-editor-tools`) qui sont injectées par le
   Twig template via le ViewBuilder. Le client écrit son Twig overlay
   ou son ViewBuilder substitué.
4. Côté PHP, même règle : pas de `use Aurora\Module\Other\…` dans
   `src/Module/Self/`. Si besoin, passer par une interface dans
   `src/Core/` que les deux modules implémentent / consomment.

**Comment vérifier** :
```bash
# Cross-module imports JS (devrait être vide hors aurora-client)
grep -rE "^import .* from ['\"]@(editorial|crm|erp|ecommerce|photo|billing|ged|hr|planning|project|notes|vault|password-generator)/" \
  src/Module/ | awk -F'/Module/' '{src=$2; split(src,a,"/"); print a[1] " : " $0}' \
  | awk -F: '{src=$1; line=$0; sub(/.*from /, "", line); sub(/^["'"'"']@/, "", line); split(line, b, "/"); tgt=b[1]; if (toupper(substr(tgt,1,1)) substr(tgt,2) != src) print src " -> @" tgt}' | sort -u
```

Voir [[convention-thin-controller]] (même esprit côté backend) et
[[pref-think-long-term]] (refacto extension-point dès qu'on l'identifie).
