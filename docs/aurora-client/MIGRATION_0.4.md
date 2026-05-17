# Migration aurora-core 0.3.x → 0.4.0

**Type** : breaking — namespaces déplacés.

Les entités Core qui appartenaient logiquement à un module parent (Platform,
Configuration, General, Media, Dev) vivent désormais dans un sous-dossier
du module. La convention est alignée sur celle déjà en place côté
`src/Module/` (Vault, Notes, Editorial…) : **1 module = 1 dossier =
sous-modules à l'intérieur**.

> **DB intacte** : aucune migration Doctrine n'est nécessaire. Les tables
> (`core_user`, `core_agency`, `core_audit_log`, `core_media`,
> `core_setting`, etc.) gardent le même nom. Seules les classes PHP
> bougent.

## Table de correspondance

| Avant 0.4.0 | Après 0.4.0 |
|---|---|
| `Aurora\Core\Dashboard\*` | `Aurora\Core\General\Dashboard\*` |
| `Aurora\Core\Profile\*` | `Aurora\Core\General\Profile\*` |
| `Aurora\Core\Search\*` | `Aurora\Core\General\Search\*` |
| `Aurora\Core\Audit\*` | `Aurora\Core\Dev\Audit\*` |
| `Aurora\Core\Setting\*` | `Aurora\Core\Configuration\Setting\*` |
| `Aurora\Core\Theme\*` | `Aurora\Core\Configuration\Theme\*` |
| `Aurora\Core\Media\*` | `Aurora\Core\Media\Library\*` |
| `Aurora\Core\User\*` | `Aurora\Core\Platform\User\*` |
| `Aurora\Core\Agency\*` | `Aurora\Core\Platform\Agency\*` |
| `Aurora\Core\Auth\*` | `Aurora\Core\Platform\Auth\*` |
| `Aurora\Core\Service\Entity\*` | `Aurora\Core\Platform\Service\Entity\*` |
| `Aurora\Core\Service\Dto\*` | `Aurora\Core\Platform\Service\Dto\*` |
| `Aurora\Core\Service\Manager\*` | `Aurora\Core\Platform\Service\Manager\*` |
| `Aurora\Core\Service\Repository\*` | `Aurora\Core\Platform\Service\Repository\*` |
| `Aurora\Core\Service\Serializer\*` | `Aurora\Core\Platform\Service\Serializer\*` |
| `Aurora\Core\Service\Controller\*` | `Aurora\Core\Platform\Service\Controller\*` |
| `Aurora\Core\Service\View\*` | `Aurora\Core\Platform\Service\View\*` |
| `Aurora\Core\Service\{Platform,Media,Configuration,General}Context` | `Aurora\Core\{Platform,Media,Configuration,General}\{Same}Context` (à la racine du folder du module) |
| `Aurora\Module\<X>\Service\<X>Context` (12 modules business) | `Aurora\Module\<X>\<X>Context` (à la racine du folder du module) |

### Inchangé (cross-cutting infra)

Les dossiers suivants ne sont pas des "sous-modules" mais de
l'infrastructure transverse et **n'ont pas bougé** : `Encryption`,
`Frontend`, `Locale`, `Mail`, `Menu`, `Migration`, `Module`,
`MountPoint`, `Notification`, `Repository`, `Scheduler`, `Sequence`,
`Storage`, `Support`, `Timestampable`, `Twig`, `Validation`.

## Procédure côté client

### 1. Mettre à jour le vendor

```bash
composer update axelraboit/aurora
# ou : make aurora-update
```

### 2. Renommer les dossiers d'extension (si présents)

Si vous étendiez `Agency` côté client, votre dossier d'extension passe de
`src/Module/Core/Agency/` à `src/Module/Core/Platform/Agency/` :

```bash
mkdir -p src/Module/Core/Platform
git mv src/Module/Core/Agency src/Module/Core/Platform/Agency

# Pareil pour User si vous étendiez User :
git mv src/Module/Core/User src/Module/Core/Platform/User
# etc.
```

### 3. Renommer les namespaces (sed bulk)

À lancer depuis la racine du projet client :

```bash
# Trouver tous les fichiers PHP qui référencent les anciens namespaces
grep -rl 'Aurora\\Core\\\(Dashboard\|Profile\|Search\|Audit\|Setting\|Theme\|User\|Agency\|Auth\)\\\|Aurora\\Core\\Media\\\|Aurora\\Core\\Service\\Entity' src tests config 2>/dev/null \
  | xargs sed -i \
    -e 's|Aurora\\Core\\Dashboard\\|Aurora\\Core\\General\\Dashboard\\|g' \
    -e 's|Aurora\\Core\\Profile\\|Aurora\\Core\\General\\Profile\\|g' \
    -e 's|Aurora\\Core\\Search\\|Aurora\\Core\\General\\Search\\|g' \
    -e 's|Aurora\\Core\\Audit\\|Aurora\\Core\\Dev\\Audit\\|g' \
    -e 's|Aurora\\Core\\Setting\\|Aurora\\Core\\Configuration\\Setting\\|g' \
    -e 's|Aurora\\Core\\Theme\\|Aurora\\Core\\Configuration\\Theme\\|g' \
    -e 's|Aurora\\Core\\Media\\|Aurora\\Core\\Media\\Library\\|g' \
    -e 's|Aurora\\Core\\User\\|Aurora\\Core\\Platform\\User\\|g' \
    -e 's|Aurora\\Core\\Agency\\|Aurora\\Core\\Platform\\Agency\\|g' \
    -e 's|Aurora\\Core\\Auth\\|Aurora\\Core\\Platform\\Auth\\|g' \
    -e 's|Aurora\\Core\\Service\\Entity\\|Aurora\\Core\\Platform\\Service\\Entity\\|g' \
    -e 's|Aurora\\Core\\Service\\Dto\\|Aurora\\Core\\Platform\\Service\\Dto\\|g' \
    -e 's|Aurora\\Core\\Service\\Manager\\|Aurora\\Core\\Platform\\Service\\Manager\\|g' \
    -e 's|Aurora\\Core\\Service\\Repository\\|Aurora\\Core\\Platform\\Service\\Repository\\|g' \
    -e 's|Aurora\\Core\\Service\\Serializer\\|Aurora\\Core\\Platform\\Service\\Serializer\\|g' \
    -e 's|Aurora\\Core\\Service\\Controller\\|Aurora\\Core\\Platform\\Service\\Controller\\|g' \
    -e 's|Aurora\\Core\\Service\\View\\|Aurora\\Core\\Platform\\Service\\View\\|g'
```

> ⚠️ **Pour les namespaces déclarés (`namespace Aurora\Core\Service\Entity;`)** : 
> Le sed ci-dessus rate les déclarations de namespace terminées par `;`
> pour les sous-namespaces Service (Entity/Dto/etc.). Si vous étendez
> l'entité Service, ajouter au sed une seconde passe ciblant exactement
> ces déclarations (cf. commit `a380781e` d'aurora-core pour le fix).

### 4. Re-générer l'autoload + vider le cache

```bash
composer dump-autoload
make cc
```

### 5. Valider

```bash
make stan      # doit être vert
make test      # doit être vert
make ft        # fix + test combiné
```

## Fichiers de config à vérifier manuellement

Si votre `config/services.yaml` ou `config/packages/*.yaml` référence des
classes Aurora directement (rare), le sed les a déjà couverts. Vérifier
par grep résiduel :

```bash
grep -rn 'Aurora\\Core\\\(Dashboard\|Profile\|Search\|Audit\|Setting\|Theme\|User\|Agency\|Auth\|Media\|Service\\\(Entity\|Dto\|Manager\|Repository\|Serializer\|Controller\|View\)\)' config/
# Doit être vide
```

## Outils Claude Code (skills) déjà mis à jour

Si vous utilisez les skills Aurora dans Claude Code, ils sont déjà à jour
côté vendor :
- `/extend-aurora-entity` génère vers les nouveaux paths
- `/add-entity` connaît la nouvelle hiérarchie
- `/check-extensibility` audit conforme à la nouvelle structure
- `/add-module` (nouveau) scaffolde sur la nouvelle convention
- `/add-submodule` (nouveau) ajoute des sous-features dans un module parent

## Justification de la décision

Voir [`decision_core_submodule_nesting.md`](../../.claude/memory/aurora-core/architecture/decision_core_submodule_nesting.md)
pour le raisonnement complet (discoverabilité, cohésion logique,
alignement avec Vault/Notes).
