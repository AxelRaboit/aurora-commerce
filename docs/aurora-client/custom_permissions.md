# Ajouter des permissions custom à un module Aurora

Aurora-core définit un jeu de permissions par module (ex : `crm.contacts.view`,
`crm.contacts.edit`). Si un projet client a besoin de permissions supplémentaires
(ex : `crm.contacts.export`, `crm.deals.archive`), voici comment les ajouter
proprement sans modifier aurora-core.

---

## Comment ça marche

Le `ModulePermissionVoter` d'aurora-core n'accorde l'accès à une permission que
si elle est déclarée dans le `PermissionRegistry`. Ce registry est alimenté par
tous les services implémentant `ModuleInterface` — et grâce à `_instanceof`,
toute classe implémentant cette interface est auto-taggée sans ligne à ajouter
dans `services.yaml`.

---

## Étape 1 — Créer un module de permissions

```php
// src/Module/ClientCrm/ClientCrmPermissionsModule.php
namespace App\Module\ClientCrm;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavPermission;

class ClientCrmPermissionsModule implements ModuleInterface
{
    public function getId(): string
    {
        // Même id que le module core → les permissions apparaissent
        // dans la section "CRM" du dashboard /dev/dashboard/permissions.
        // Utiliser un id différent crée une section séparée.
        return 'crm';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('crm.contacts.export'),
            new NavPermission('crm.deals.archive'),
        ];
    }

    public function getNavSections(): array
    {
        return []; // pas de sidebar, uniquement des permissions
    }
}
```

**Pas besoin** de toucher `services.yaml` — `_instanceof` s'en charge.

---

## Étape 2 — Ajouter les traductions

Sans traduction, le dashboard `/dev/dashboard/permissions` affiche la clé brute.

```yaml
# translations/messages.fr.yaml
backend:
  permissions:
    names:
      crm:
        contacts:
          export: Exporter les contacts (CSV)
        deals:
          archive: Archiver les opportunités
```

```yaml
# translations/messages.en.yaml
backend:
  permissions:
    names:
      crm:
        contacts:
          export: Export contacts (CSV)
        deals:
          archive: Archive deals
```

---

## Étape 3 — Utiliser la permission

```php
// src/Module/ClientCrm/Contact/Controller/ExportController.php
#[IsGranted('crm.contacts.export')]
public function export(): Response
{
    // ...
}
```

Côté Vue :
```js
const canExport = computed(() => can('crm.contacts.export'));
```

---

## Règles d'accès (héritées d'aurora-core)

| Rôle | Comportement |
|---|---|
| `ROLE_DEV` | Accès accordé automatiquement à toutes les permissions |
| `ROLE_ADMIN` | Accès accordé automatiquement à toutes les permissions |
| `ROLE_USER` | Accès accordé seulement si la permission est dans `$user->getPrivileges()` |

Les privilèges d'un utilisateur `ROLE_USER` se gèrent depuis
`/backend/users → Modifier → Privilèges`.

---

## Plusieurs modules de permissions

Rien n'empêche d'avoir plusieurs modules de permissions clients, un par domaine :

```
src/Module/ClientCrm/ClientCrmPermissionsModule.php   → id 'crm'
src/Module/ClientBilling/ClientBillingPermissionsModule.php → id 'billing'
src/Module/ClientHr/ClientHrPermissionsModule.php     → id 'hr'
```

Chacun s'enregistre automatiquement via `_instanceof`.
