# Variante "Manager à hooks multiples" (style User)

## Règle

Un Manager n'expose **pas** de hook `applyInput()` — uniquement des hooks
`create<X>()` + audit + ses méthodes publiques métier overridables une par
une. Réservée aux Managers qui réunissent les **3 critères** :

1. **≥6 méthodes publiques métier distinctes** (changePassword,
   consumeInvitation, toggleDevRole, …).
2. **Aucun flow create+update simple via DTO unique** n'existe.
3. **Validation/sécurité distincte** par opération (transitions de statut,
   autorisations spécifiques, contextes différents).

## Pourquoi

Forcer un `applyInput()` unique sur User ou Invoice serait artificiel :
chaque méthode représente un cas d'usage métier avec ses propres règles
(sécurité du changePassword ≠ validation de l'invitation ≠ workflow de
transitions de statut Invoice).

## Comment l'appliquer

### Identifier la variante

Au moment d'instrumenter, vérifier les 3 critères :
- ✅ User → variante (changePassword, consumeInvitation, toggleDevRole,
  updateProfile, updateAgencyAndService, requestPasswordReset, …)
- ✅ Invoice → variante (validate, delete, updateField,
  createFromOcrDraft, updateFromOcrDraft, createCreditNote)
- ✅ Order → variante (createFromCart, markPaid, markShipped,
  markDelivered, cancel, checkout)
- ✅ Tiers, OcrJob, Comment → variante (lifecycle exclusivement domain
  events)
- ❌ Agency, Service, Theme, Company, Contact, Deal → standard
  (create/update/delete via DTO unique)

### Implémenter

```php
class UserManager implements UserManagerInterface
{
    // Hooks d'instanciation (toujours requis)
    protected function createUser(): UserInterface { return new User(); }

    // PAS de applyInput

    // Hook audit (toujours requis si AuditLogger utilisé) — mais souvent
    // remplacé par un AuditUserManagerDecorator (pattern d'aurora-core User)

    // Méthodes publiques métier — overridables individuellement par le client
    public function changePassword(UserInterface $user, string $newPassword): void { … }
    public function consumeInvitation(UserInterface $user, …): void { … }
    public function toggleDevRole(UserInterface $user): void { … }
    // … 20+ autres
}
```

### Documentation in-Manager

Ajouter un commentaire au-dessus du hook `create<X>()` :

```php
/**
 * Single instantiation hook : business operations are overridden via
 * individual public methods (changePassword, consumeInvitation, …).
 * The Manager does not expose applyInput() because no simple create+update
 * via DTO unique exists — see CLAUDE.md memory variant_user_style.
 */
protected function createUser(): UserInterface
{
    return new User();
}
```

## Variante 1bis : composables Vue séparés

User et Theme ont **deux** composables Vue (`useUsersInvite` +
`useUsersForm`, `useThemesCreate` + `useThemesEdit`) car les forms sont
fonctionnellement différents (form invite ≠ form edit user, form create
theme barebones ≠ form edit theme avec config CSS panel).

Slots correspondants :
- `extra-invite-form-fields` + `extra-form-fields` (User)
- `extra-create-form-fields` + `extra-form-fields` (Theme)

Pour toutes les autres entités, **un seul** composable `useXxxForm.js`
unifié + slot unique `extra-form-fields`.

## Source

Section 4.bis du doc convention. Pilote validé sur User d'abord, puis
appliqué uniformément à Order/Invoice/Tiers/OcrJob/Comment lors du rollout.
