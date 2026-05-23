# Welding — nouveau module Aurora

> **Statut** : décisions structurelles actées (mai 2026). Aucun code écrit.
> Document vivant — le doc se découpera en sous-fichiers thématiques (à la
> [`nimbus/`](../nimbus/README.md)) une fois la V1 lancée.
>
> **Prochaine étape** : sprint 0 = audit du mécanisme signature canvas →
> embed PDF dans `PdfForm` (blocker à lever avant de scaffolder Welding).

Module pour piloter des **workflows de soudure réglementée** (cible
initiale : nucléaire — RCC-M, ASME III, normes ISO 15614). Un superviseur
qualité définit un *modèle de workflow* segmenté en étapes ; chaque étape
embarque un ou plusieurs PDF à remplir, et certaines étapes ne se valident
qu'avec la signature d'un tiers (inspecteur, QA). Le soudeur traverse les
étapes du workflow, remplit les PDFs, signe ; les validateurs signent à
leur tour ; le dossier complet est archivable.

Ce module est aussi le **banc d'essai** du pipeline complet
« import PDF → définir champs → remplir → signer → archiver » que
[`PdfForm`](../../../../src/Module/PdfForm/) a outillé en briques mais
pas encore mis en scène dans un cas d'usage de bout en bout.

---

## Naming — **acté : `Welding`**

Convention Aurora : *nom de domaine, pas nom de produit* (cf.
`Editorial`, `Ecommerce`, `Notes`, `PersonalFinance`).

Alternatives écartées :

| Candidat | Pourquoi écarté |
|---|---|
| `WeldQa` | Acronyme, moins lisible |
| `QualityWorkflow` | Violerait « pas d'abstraction sans 2e implémenteur plausible » (CLAUDE.md §3bis) |
| `WeldingDossier` | Long, et `Welding` couvre déjà la sémantique |

Si un second usecase apparaît plus tard (workflow d'inspection mécanique,
contrôle non-destructif autonome, maintenance procédurale), on **extraira**
alors un sous-module `Workflow` générique réutilisable. Pas avant.

Implications du choix `Welding` :

- Folder : `src/Module/Welding/`
- Namespace : `Aurora\Module\Welding\`
- DB tables : `core_welding_*`
- Sequences : `seq_core_welding_<entity>_id` ([convention §1.1](../../dev/entity_extensibility_convention.md))
- Routes backend : `/backend/welding/*`
- Twig namespace : `@Welding/`
- Translations : `translations/welding.<locale>.yaml`
- Storage : `var/uploads/welding/<workflow_ref>/` pour les pièces jointes
  spécifiques (photos de cordon, radios, etc.) — les PDFs générés
  restent sous `var/uploads/pdf-form/` (gérés par PdfForm)

---

## Pourquoi maintenant

1. **Banc d'essai PdfForm bout-en-bout** : PdfForm a livré `PdfTemplate`,
   `PdfTemplateField` (typés Text/Checkbox/Radio/Dropdown/Date/`Signature`)
   et `PdfDocument` (avec `contextType`/`contextId` polymorphique).
   Aucun usecase Aurora ne met aujourd'hui ces briques en scène dans un
   workflow réel — Welding est ce cas.
2. **Domaine réglementé = adoption industrielle** : la traçabilité
   PDF + signature + validation tierce + archive est un besoin transverse
   (industrie nucléaire, naval, aérospatial, chaudronnerie sous pression).
3. **Test de la convention extensibilité** sur un module dont les clients
   auront *de fait* besoin d'étendre (chaque client industriel ajoute ses
   champs propres : n° de cordon, lot matière, traitement thermique, etc.).

---

## Modèle de données — proposition

### Couche « Template » (définie par le superviseur qualité)

| Entité | Rôle | Champs notables |
|---|---|---|
| `WorkflowTemplate` | Modèle de procédure | `title`, `description`, `version`, `applicableTo` (ex: TIG/MIG/SAW), `status` (Draft/Published/Archived) |
| `WorkflowStepTemplate` | Étape ordonnée d'un modèle | `position`, `title`, `description`, `requiresValidation` (bool), `validatorRole` (ex: Inspector, QA, Supervisor) |
| `WorkflowStepPdfTemplate` | Lien N:M step ↔ `PdfTemplate` | `step`, `pdfTemplate` (PdfForm), `required` (bool), `position` |

> `WorkflowStepPdfTemplate` réutilise directement `PdfTemplateInterface`
> de `Module/PdfForm` — pas de duplication.

### Couche « Instance » (exécution réelle par un soudeur)

| Entité | Rôle | Champs notables |
|---|---|---|
| `Workflow` | Instance d'un template | `reference` (auto, `WLD-2026-000001`), `template`, `assignee` (Employee soudeur), `status` (Draft/InProgress/AwaitingValidation/Completed/Rejected/Archived), `currentStep`, `startedAt`, `completedAt`, `contextType`/`contextId` (optionnel — rattache à un Project/Affaire) |
| `WorkflowStep` | État d'une étape de l'instance | `position`, `template` (WorkflowStepTemplate), `status` (Pending/InProgress/AwaitingValidation/Validated/Rejected), `completedBy` (User soudeur), `completedAt`, `validatedBy` (User validator), `validatedAt`, `validationComment` |

> Les `PdfDocument` générés pendant une step pointent vers `WorkflowStep`
> via `contextType = 'welding_step'` + `contextId = step.id`. **Zéro
> nouveau modèle pour le rattachement** — c'est exactement le scénario
> pour lequel `contextType`/`contextId` ont été conçus.

### Couche optionnelle « multi-validation » (V2 si besoin)

| Entité | Rôle |
|---|---|
| `WorkflowStepValidation` | Si une step doit collecter plusieurs signatures de validateurs (ex: inspecteur + QA + client) : un enregistrement par validateur avec décision + comment + signature |

À reporter V2 si V1 = 1 validateur max par step.

---

## Workflows (statuts & transitions)

### Workflow global

```
Draft → InProgress → AwaitingValidation → Completed
                  ↘                    ↘
                    Rejected             Archived
```

- `Draft` : créé, pas démarré
- `InProgress` : soudeur travaille
- `AwaitingValidation` : au moins une step en attente de signature tierce
- `Completed` : toutes les steps validées
- `Rejected` : au moins une step rejetée (à reprendre)
- `Archived` : verrouillé, lecture seule, archivable en GED

### Step

```
Pending → InProgress → AwaitingValidation → Validated
                                          ↘
                                            Rejected → InProgress
```

- Si `requiresValidation = false` → passe directement `InProgress` → `Validated` à la signature soudeur
- Si `requiresValidation = true` → `InProgress` → `AwaitingValidation` → `Validated` (par le validator) ou `Rejected` (retour `InProgress` pour le soudeur)

---

## UI / Vue 3 — 3 surfaces

### 1. Éditeur de WorkflowTemplate (admin / superviseur)

- Liste des templates (CRUD classique Aurora)
- Éditeur : titre, version, applicableTo, drag&drop des steps, attach
  des `PdfTemplate` à chaque step (sélecteur dans la liste PdfForm existante)
- Toggle `requiresValidation` + `validatorRole` par step
- Bouton « Publier » : passe `Draft → Published` (devient sélectionnable
  pour démarrer un workflow)

### 2. Runner step-by-step (soudeur)

- Page « Mes workflows » : kanban par status, mes workflows assignés
- Démarrer un workflow → wizard ordonné step par step
- Pour chaque step : afficher les PDFs à remplir (viewer/filler PdfForm
  intégré), bouton « Marquer cette step complète »
- Si `requiresValidation` → step passe `AwaitingValidation`, notif au validator
- Sinon → step passe `Validated`, le wizard avance à la step suivante

### 3. Page validateur (inspecteur / QA)

- Liste « Validations en attente » filtrée par `validatorRole`
- Détail d'une step à valider : voir tous les PDFs remplis + commentaire
  soudeur
- Actions : `Valider` (signature canvas → embed dans un PDF de validation)
  ou `Rejeter` (avec comment obligatoire)

---

## Permissions

| Permission | Qui | Action |
|---|---|---|
| `WELDING_TEMPLATE_VIEW` | Tous les loggés du module | Voir les templates |
| `WELDING_TEMPLATE_MANAGE` | Superviseur qualité | CRUD WorkflowTemplate + WorkflowStepTemplate |
| `WELDING_WORKFLOW_START` | Soudeur | Démarrer une instance |
| `WELDING_WORKFLOW_FILL` | Soudeur (l'assignee) | Remplir les steps de son workflow |
| `WELDING_WORKFLOW_VALIDATE` | Validators (Inspector/QA/Supervisor) | Valider/rejeter les steps |
| `WELDING_WORKFLOW_ARCHIVE` | Superviseur | Archiver un workflow terminé |

Pattern Aurora existant : voir [`per_user_module_access.md`](../../dev/per_user_module_access.md).

---

## Intégrations modules Aurora

| Module | Lien | V1 ? |
|---|---|---|
| **`PdfForm`** | Dépendance dure : `WorkflowStepPdfTemplate` référence `PdfTemplateInterface` ; les PDFs remplis sont des `PdfDocument` | ✅ V1 |
| **`Hr/Employee`** | `Workflow.assignee` = `Employee` (le soudeur). Permet d'avoir nom/prénom/matricule du soudeur sur les PDFs générés | ✅ V1 |
| **`Configuration`** | Settings du module (refs auto-numbering pattern, durée de conservation, etc.) | ✅ V1 |
| **`Notifications`** *(à confirmer)* | Notif validator quand step passe `AwaitingValidation` | ✅ V1 si dispo, V2 sinon |
| **`Crm/Project`** | `Workflow.contextType=project / contextId` pour rattacher un workflow à un projet client | 🟡 V2 |
| **`Ged`** | Archive auto du dossier complet (PDFs signés + métadonnées JSON) en GED à `Completed` | 🟡 V2 |
| **`Configuration` (toggle)** | Enregistrer dans `/dev/dashboard/modules` (skill `register-module-toggle`) | ✅ V1 |

---

## Décisions actées (mai 2026)

> Toutes les décisions structurelles ont été tranchées avec l'utilisateur.
> Cette section est la **source de vérité** pour le scaffold V1.

### 1. Nom du module — **`Welding`**

Convention Aurora « nom de domaine ». Implications : voir section Naming.

### 2. Périmètre — **module spécifique-soudure, pas de couche `Workflow` générique**

Concret > abstrait (CLAUDE.md §3bis garde-fou #1). Si un 2e usecase
émerge (inspection / maintenance procédurale), on extrait alors `Workflow`
en sous-module partagé. Pas avant.

### 3. Soudeur — **`Hr/Employee`, pas `User` directement**

`Workflow.assignee` est typé `EmployeeInterface`. Le matricule et le poste
du soudeur viennent de `Employee` et apparaissent sur les PDFs générés.
L'auth applicative passe par `Employee.user` (relation existante).

### 4. Versionning des templates — **immutable + clone+bump (option b)**

`WorkflowTemplate` devient **read-only** une fois passé en `Published`.
Modification → bouton « Nouvelle version » qui clone l'arbre
(template + steps + step-pdf) et bump `version`. Les workflows en cours
restent accrochés à leur version d'origine. Pas de snapshot des steps
dans l'instance — `Workflow.template` est une simple FK figée.

**Tradeoff accepté** : friction admin pour corriger une typo (doit
passer par v2) en échange d'une garantie réglementaire forte (un
template « validé » ne change plus).

### 5. Signature canvas → PDF — **blocker confirmé, sprint 0.5 requis** ⚠️

**Audit effectué (mai 2026)** : la capture canvas **n'est PAS embarquée**
dans le PDF généré aujourd'hui. État actuel détaillé :

| Couche | État |
|---|---|
| Frontend `SignaturePad.vue` (canvas) | ✅ Implémenté — capture en `data:image/png;base64,...` |
| Frontend `SignatureDisplay.vue` (rendu read-only) | ✅ Implémenté |
| Frontend `usePdfDocumentsForm.js` | ✅ Injecte `__signature__: <dataUrl>` dans `fieldValues` |
| Backend `PdfTemplate.requiresSignature` (bool template) | ✅ Persisté, un toggle au niveau du template entier |
| Backend `PdfDocumentManager::generate()` | ❌ Passe `fieldValues` brut au manipulator sans extraire `__signature__` |
| Backend `PdfManipulator::fill()` + `tools/pdf/fill.mjs` | ❌ Ne connaît que les types AcroForm standard (Text, CheckBox, RadioGroup, Dropdown, OptionList, Button). Aucune branche pour `PDFSignature` |
| Net résultat | La data URL `__signature__` est silencieusement abandonnée par le `try/catch` de `fill.mjs:90-95`. **Le PDF généré ne contient pas la signature.** |

**Plan sprint 0.5** (PR séparée avant Welding) :

1. Dans `PdfDocumentManager::generate()` : extraire `__signature__` des
   `fieldValues` avant l'appel `pdfManipulator->fill()`. Passer la data URL
   en paramètre dédié.
2. Étendre `PdfManipulator::fill()` (ou ajouter `fillWithSignature()`)
   pour transmettre la signature au script Node.
3. Dans `tools/pdf/fill.mjs` :
   - Détecter le champ de type `PDFSignature` (`field.constructor.name`)
   - Récupérer son rectangle via `field.acroField.getWidgets()[0].getRectangle()`
     + sa page
   - Décoder la data URL → bytes PNG → `pdfDoc.embedPng(bytes)`
   - `page.drawImage(pngImage, { x, y, width, height })` aux coords du widget
4. Si `flatten = true`, l'image est conservée dans le rendu final.

**Effort estimé** : 0.5 à 1 jour.

**Note de scope** : `PdfTemplate.requiresSignature` est aujourd'hui un
**bool sur le template entier** (une seule signature finale par PDF
généré), pas une signature par champ. Pour Welding V1, ça suffit : si
une step demande 3 signatures différentes (soudeur, inspecteur, QA),
on génère 3 `PdfDocument` rattachés à la step (1 PDF = 1 signature).
Multi-signature par PDF = V2 si besoin réel.

### 6. Multi-validation par step — **V1 = single validator**

`WorkflowStep` porte directement `validatedBy` / `validatedAt` /
`validationComment`. Pas d'entité `WorkflowStepValidation` en V1.

Si demain un client demande 2+ signatures par step (inspecteur + QA +
client final) → V2 sans casser V1 : la colonne `validatedBy` devient
`firstValidatedBy`, les signatures additionnelles vont dans une table
dédiée.

### 7. Référence auto-numérotée — **`WLD-{YYYY}-{NNNNNN}` (reset annuel)**

Format : `WLD-2026-000042`. Compteur 6 chiffres, reset au 1er janvier.

Le **préfixe `WLD`** est exposé comme `Setting` admin
(`Module/Welding/Setting/`) — un client francophone peut le passer à
`SOUD` ou autre. Reset annuel et largeur du compteur (6) restent en dur.

### 8. Statut « Rejeté » — **deux sémantiques séparées**

- **Rejet d'une step** par un validator → la step repasse `InProgress`,
  le soudeur la corrige et re-soumet. Le `Workflow` reste `InProgress`.
  Le commentaire du rejet est conservé en historique (audit).
- **Rejet du workflow entier** → action **explicite** du superviseur
  (bouton « Rejeter le workflow »), terminal, lecture seule, motif
  obligatoire. Pour les cas type non-conformité majeure.

### 9. Archive Ged auto en `Completed` — **V2**

V1 = bouton manuel « Archiver en GED » sur les workflows `Completed`.
V2 = automatisation déclenchée par event `WorkflowCompletedEvent` →
listener qui copie en GED.

### 10. Notifications validator — **V1 = email Symfony Mailer simple**

Un email envoyé au validator quand une step passe `AwaitingValidation`.
Pas de framework notification générique avant qu'un besoin transversal
soit identifié.

### 11. Dépendance PdfForm — **vérification au boot du module**

`WeldingModule` implémente un check au bootstrap : si PdfForm est
désactivé dans le toggle dashboard, Welding refuse de s'activer et
affiche un message clair « Activez PdfForm d'abord ». **Premier cas
d'inter-dépendance de modules dans Aurora** — la convention sera posée
proprement et documentée pour les futurs modules dépendants.

---

## Pièges identifiés

- **Versionning template** : voir question #4 ci-dessus. Sans stratégie,
  une modif de template casserait les workflows en cours.
- **Signature → PDF embed** : à valider sur PdfForm avant de lancer Welding.
  Si le mécanisme actuel n'embed pas la signature canvas dans le PDF
  final, c'est un blocker.
- **Notification validator** : si Aurora n'a pas encore de framework
  notification générique, prévoir un fallback email simple (Mailer
  Symfony) pour V1.
- **Dépendance PdfForm activé** : le toggle dashboard doit refuser
  d'activer Welding si PdfForm est désactivé. Pattern à concevoir
  (premier cas d'inter-dépendance de modules dans Aurora ?).
- **Storage des photos cordon** : si on accepte des photos jointes
  (radios, macrographies), prévoir sous `var/uploads/welding/<workflow_ref>/`
  + route `/uploads/welding/...` avec auth granulaire (cf.
  `convention_storage_var_uploads.md`).
- **Audit trail réglementaire** : nucléaire = traçabilité légale. Tous
  les changements de status, signatures, validations doivent être
  immutables et timestampés. Aurora a déjà un système d'audit
  (`TimestampableTrait`, `AuditPayload`) — vérifier qu'il couvre le
  besoin.

---

## Effort estimé V1

| Bloc | Estimation |
|---|---|
| Backend : 5 entités × 5 couches Sylius (Entity / DTO / Manager / Serializer / Controller) + Repository + Migration | ~3-4 jours (mécanique avec skill `add-entity`) |
| Vue 3 — éditeur template (admin) | ~1-2 jours |
| Vue 3 — runner step-by-step (soudeur, le plus original) | ~3-4 jours |
| Vue 3 — page validateur | ~1 jour |
| Permissions + toggle + audit + i18n | ~1-2 jours |
| Tests PHP (manager hooks, transitions de status, permissions) | ~2 jours |
| **Total** | **~12-15 jours focalisés** |

---

## Étapes d'implémentation

1. ✅ **Sprint -1 — décisions** : 11 décisions actées (cf. section ci-dessus).
2. ✅ **Sprint 0 — audit PdfForm signature** : blocker confirmé (cf. décision #5).
3. ⏳ **Sprint 0.5 — instrumenter signature embed dans PdfForm** : PR séparée,
   ~0.5-1 jour. Détaillée dans la décision #5 ci-dessus.
4. ⏸ **Sprint 1 — module + entités template** : `aurora:make:module Welding`
   + `WorkflowTemplate` + `WorkflowStepTemplate` + `WorkflowStepPdfTemplate`,
   CRUD admin V1, migration.
4. ⏸ **Sprint 2 — entités instance** : `Workflow` + `WorkflowStep`,
   state machine de transition, hooks Manager. Référence
   auto-numérotée `WLD-{YYYY}-{NNNNNN}` via Setting `welding.reference_prefix`.
5. ⏸ **Sprint 3 — UI runner soudeur** : cœur du module. Wizard step-by-step,
   intégration PdfForm filler.
6. ⏸ **Sprint 4 — UI validateur + notifications email** : page validateur +
   email Symfony Mailer simple sur `AwaitingValidation`.
7. ⏸ **Sprint 5 — toggle + dépendance PdfForm + permissions + i18n + audit** :
   finalisation. Premier cas d'inter-dépendance de modules (PdfForm requis).
8. ⏸ **V2 (post-V1)** : archive Ged auto, multi-validation par step,
   rattachement Project/Affaire (`contextType`/`contextId` sur `Workflow`).
