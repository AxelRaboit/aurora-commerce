# PersonalFinance — Importer (Excel)

## Contexte

Import en masse de transactions depuis un fichier Excel `.xlsx`. Flux en
2 étapes :

1. **Upload** : utilisateur sélectionne wallet cible + fichier Excel,
   serveur parse et retourne preview (lignes + suggestions de catégorie
   via [`auto_categorisation.md`](auto_categorisation.md))
2. **Confirmation** : utilisateur peut éditer ligne par ligne, retirer
   des lignes, appliquer une catégorie en masse, puis valide → création
   batch des transactions

Source Spendly :
- `app/Services/ImportService.php`
- `app/Http/Controllers/ImportController.php`
- `app/Http/Requests/{UploadImportFileRequest,ProcessImportRequest}.php`
- `resources/js/Pages/Import/Index.vue`

## Format Excel (template fourni)

Endpoint `GET /backend/personal-finance/import/template` retourne un `.xlsx`
généré dynamiquement avec :
- Ligne 1 : headers stylisés (`Date`, `Montant`, `Type`, `Description`,
  `Tags`)
- Lignes 2-4 : exemples
- Onglet "Instructions" avec :
  - Format date accepté : `YYYY-MM-DD` ou `DD/MM/YYYY`
  - Format montant : numérique positif, séparateur `.` ou `,`
  - Type : `expense` ou `income` (insensible casse)
  - Tags : séparés par `;`

## Service `PersonalFinanceImportService`

```php
class PersonalFinanceImportService
{
    private const TEMPLATE_HEADERS = ['Date', 'Montant', 'Type', 'Description', 'Tags'];

    public function generateTemplate(): Spreadsheet;

    /** @return array{rows: array<int, PersonalFinanceImportRowDto>, total: int, suggestedMonth: ?string} */
    public function preview(UploadedFile $file): array
    {
        // 1. validate mime + size (max 5 MB)
        // 2. PhpSpreadsheet load
        // 3. valider headers === TEMPLATE_HEADERS
        // 4. parser chaque ligne en PersonalFinanceImportRowDto (date parsée, montant float, type enum, description, tags array)
        // 5. (optionnel) appeler PersonalFinanceCategorizationSuggestService::suggestBulk()
        //    pour pré-remplir les catégories suggérées
        // 6. détecter le mois le plus fréquent (pour la navigation post-import)
    }

    /** @return array{created: int, errors: array<int, string>, month: ?string} */
    public function process(User $user, PersonalFinanceWallet $wallet, array $rows): array
    {
        // wraps in DB transaction
        // foreach row: financeTransactionManager->create(...)
        // catch et accumule les erreurs ligne par ligne (ne stoppe pas)
        // return summary
    }
}
```

`PersonalFinanceImportRowDto` est un value object read-only :
```php
final readonly class PersonalFinanceImportRowDto
{
    public function __construct(
        public DateImmutable $date,
        public string $amount,                   // string pour éviter les pertes precision
        public PersonalFinanceTransactionType $type,
        public ?string $description,
        public array $tags = [],
        public ?PersonalFinanceCategory $suggestedCategory = null,
    ) {}
}
```

## Service `PersonalFinanceImportTemplateService`

Séparé du `PersonalFinanceImportService` pour respecter SRP. Une seule méthode
`generateTemplate(): Spreadsheet` — génère le `.xlsx` template stylisé.

## Controllers

- `GET /backend/personal-finance/import` → page upload
- `GET /backend/personal-finance/import/template` → download template
- `POST /backend/personal-finance/import/preview` (file upload + wallet_id) →
  JSON preview
- `POST /backend/personal-finance/import/process` (rows JSON + wallet_id) → 
  redirect vers wallet budget view avec flash success

> **Pas de stockage long-terme du fichier uploadé.** Parse en mémoire,
> retour JSON, fichier oublié. Le client renvoie le payload JSON pour
> le process (pas idéal pour gros volumes mais OK pour V1, max ~500
> lignes). Si demande > 1000 lignes : passer à un stockage temporaire
> `var/cache/personal-finance/imports/{userId}/{uuid}.json` + ID référence.

## DTO + validation Symfony

`PersonalFinanceImportProcessInput` :
- `wallet: PersonalFinanceWallet` (résolu via ParamConverter)
- `rows: array<PersonalFinanceImportRowInput>` (validation Symfony Constraints
  pour chaque ligne : date pas dans le futur > 1 mois, amount > 0, type
  enum value, catégorie optionnelle dans le wallet)

Refuser le batch entier si > 1000 lignes (sécurité).

## Vue

`src/Module/PersonalFinance/assets/backend/import/ImportApp.vue` :
- **Step 1** :
  - Lien "Télécharger le template"
  - Dropdown wallet cible
  - Zone drag-drop (ou input file)
  - Bouton "Prévisualiser"
- **Step 2** :
  - Table avec lignes parsées
  - Colonne catégorie : dropdown éditable, avec catégorie suggérée pré-sélectionnée si fournie
  - Bouton "Appliquer cette catégorie à toutes les lignes restantes vides"
  - Action retirer ligne
  - Bouton "Importer X transactions"
- Toast erreurs par ligne après process

Composable `usePersonalFinanceImportPreview.js` gère l'état des 2 steps + appels
API.

## Side-effects

- Chaque transaction créée passe par `PersonalFinanceTransactionManager::create()`,
  qui :
  - Apprend la catégorisation (via `afterSave` hook → 
    `PersonalFinanceCategorizationLearnService::learn`)
  - Déclenche `TransactionSavedEvent` → goals auto-trackés re-synchros
- Pas d'auto-recompute du budget (les actuals sont calculés à la lecture
  cf. [`portefeuilles.md`](portefeuilles.md) §Budget)

## Extensibilité

- Override `PersonalFinanceImportService::preview()` pour supporter des formats
  custom (CSV bancaire, OFX, XML…) — exposer un point d'extension via
  une interface `PersonalFinanceImportParserInterface` enregistrée comme tagged
  service `personal_finance.import.parser` ; chaque parser annonce son mime type
  + nom

Pour V1 : un seul parser (PhpSpreadsheet `.xlsx`). Interface tracée
dès le départ pour permettre l'extension future sans refacto.

```php
interface PersonalFinanceImportParserInterface
{
    public function supports(UploadedFile $file): bool;
    public function parse(UploadedFile $file): array; // array<PersonalFinanceImportRowDto>
}
```

- Slot Vue `extra-template-instructions` dans la page d'upload

## Pointeurs

- Spendly : `ImportService.php` (à lire pour la logique exacte de
  parsing date/montant), `Import/Index.vue`
- Aurora : 
  - PhpSpreadsheet : déjà disponible dans Aurora (via
    `Aurora\Module\Billing\Service\InvoiceExcelExportService`)
  - Pattern multi-parser tagged services :
    `Aurora\Module\Ged\Document\Parser\*` (si existant — sinon, on
    établit le pattern)
