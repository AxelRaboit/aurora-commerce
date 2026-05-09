# Créer un module client

Un module client est un ensemble de fonctionnalités propres au projet qui
n'existent pas dans aurora-core. Il suit la même architecture que les modules
Aurora (Entity, Manager, Repository, Serializer, Controller, Vue, Translations)
mais vit entièrement dans `src/Module/<Name>/`.

Ce guide utilise **Tracking** comme exemple de référence (déjà implémenté).

---

## Structure cible

```
src/Module/Tracking/
├── TrackingModule.php              # enregistrement nav + permissions
├── TrackingFrontend.php            # (optionnel) entrypoint frontend public
├── Enum/
│   └── ProjectStatusEnum.php
├── Project/
│   ├── Entity/
│   │   └── Project.php
│   ├── Dto/
│   │   └── ProjectInput.php
│   ├── Manager/
│   │   ├── ProjectManagerInterface.php
│   │   └── ProjectManager.php
│   ├── Repository/
│   │   └── ProjectRepository.php
│   ├── Serializer/
│   │   └── ProjectSerializer.php
│   ├── View/
│   │   └── ProjectsViewBuilder.php
│   └── Controller/
│       └── Admin/
│           └── ProjectsController.php
└── translations/
    ├── messages.fr.yaml
    └── messages.en.yaml
```

---

## Étape 1 — Déclarer le module

```php
// src/Module/Tracking/TrackingModule.php
namespace App\Module\Tracking;

use Aurora\Core\Module\ModuleInterface;
use Aurora\Core\Module\NavItem;
use Aurora\Core\Module\NavPermission;
use Aurora\Core\Module\NavSection;

class TrackingModule implements ModuleInterface
{
    public function getId(): string
    {
        return 'tracking';
    }

    public function getNavSections(): array
    {
        return [
            new NavSection(
                id: 'tracking',
                label: 'admin.nav.tracking',
                icon: 'layout-dashboard',
                items: [
                    new NavItem(
                        label: 'admin.nav.tracking.projects',
                        route: 'tracking_admin_projects_index',
                        permission: 'tracking.projects.manage',
                    ),
                ],
            ),
        ];
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('tracking.projects.manage'),
            new NavPermission('tracking.projects.delete'),
        ];
    }
}
```

Enregistrer dans `config/services.yaml` :

```yaml
App\Module\Tracking\TrackingModule:
    tags: [aurora.module]
```

Puis synchroniser :

```bash
make sf CMD="aurora:privileges:sync"
make sf CMD="aurora:menus:sync"
```

---

## Étape 2 — Entité

```php
// src/Module/Tracking/Project/Entity/Project.php
namespace App\Module\Tracking\Project\Entity;

use Aurora\Core\Contract\TimestampableInterface;
use Aurora\Core\Trait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'tracking_projects')]
#[ORM\HasLifecycleCallbacks]
class Project implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_tracking_project_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $title = '';

    // … autres champs

    public function getId(): ?int { return $this->id; }
    // … getters/setters
}
```

Générer la migration :

```bash
make migration && make migrate
```

---

## Étape 3 — DTO

```php
// src/Module/Tracking/Project/Dto/ProjectInput.php
namespace App\Module\Tracking\Project\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProjectInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $title,

        #[Assert\NotBlank]
        public string $description,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: trim($data['title'] ?? ''),
            description: trim($data['description'] ?? ''),
        );
    }
}
```

> Pour un module client simple sans besoin d'extensibilité client-du-client,
> un DTO `final readonly` avec `fromArray()` statique suffit. Le pattern
> complet (Interface + Factory) n'est requis que si l'entité doit être
> extensible par d'éventuels consommateurs.

---

## Étape 4 — Manager

```php
// src/Module/Tracking/Project/Manager/ProjectManager.php
namespace App\Module\Tracking\Project\Manager;

use App\Module\Tracking\Project\Dto\ProjectInput;
use App\Module\Tracking\Project\Entity\Project;
use Aurora\Core\Audit\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;

class ProjectManager implements ProjectManagerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function create(ProjectInput $input): Project
    {
        $project = new Project();
        $project->setTitle($input->title);
        $project->setDescription($input->description);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $this->auditLogger->log('tracking', 'project.created', 'Project', $project->getId(), [
            'title' => $project->getTitle(),
        ]);

        return $project;
    }

    public function update(Project $project, ProjectInput $input): void
    {
        $project->setTitle($input->title);
        $project->setDescription($input->description);
        $this->entityManager->flush();

        $this->auditLogger->log('tracking', 'project.updated', 'Project', $project->getId(), [
            'title' => $project->getTitle(),
        ]);
    }

    public function delete(Project $project): void
    {
        $this->entityManager->remove($project);
        $this->entityManager->flush();

        $this->auditLogger->log('tracking', 'project.deleted', 'Project', $project->getId(), []);
    }
}
```

---

## Étape 5 — Repository

```php
// src/Module/Tracking/Project/Repository/ProjectRepository.php
namespace App\Module\Tracking\Project\Repository;

use App\Module\Tracking\Project\Entity\Project;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findPaginated(int $page, int $limit, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('p.title LIKE :search')->setParameter('search', "%{$search}%");
        }

        return $this->paginate($qb, $page, $limit);
    }
}
```

> Pour les modules client, `ServiceEntityRepository` est acceptable —
> contrairement aux entités Aurora, les entités client n'ont pas besoin
> d'être substituables via `resolve_target_entities`.

---

## Étape 6 — Controller

```php
// src/Module/Tracking/Project/Controller/Admin/ProjectsController.php
namespace App\Module\Tracking\Project\Controller\Admin;

use App\Module\Tracking\Project\Dto\ProjectInput;
use App\Module\Tracking\Project\Manager\ProjectManagerInterface;
use App\Module\Tracking\Project\Repository\ProjectRepository;
use App\Module\Tracking\Project\View\ProjectsViewBuilder;
use Aurora\Core\Controller\Trait\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/tracking/projects', name: 'tracking_admin_projects_')]
#[IsGranted('tracking.projects.manage')]
class ProjectsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectManagerInterface $projectManager,
        private readonly ProjectsViewBuilder $viewBuilder,
        private readonly PayloadValidator $validator,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('@Tracking/admin/projects/index.html.twig',
            $this->viewBuilder->indexView()
        );
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        return $this->jsonSuccess($this->viewBuilder->buildListPayload(
            page: $request->query->getInt('page', 1),
            search: $request->query->getString('search'),
        ));
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $input = ProjectInput::fromArray($request->toArray());

        if ($errors = $this->validator->validate($input)) {
            return $this->jsonInvalidInput($errors);
        }

        $project = $this->projectManager->create($input);

        return $this->jsonSuccess(['id' => $project->getId()]);
    }

    #[Route('/{id}/update', name: 'update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            return $this->jsonNotFound();
        }

        $input = ProjectInput::fromArray($request->toArray());

        if ($errors = $this->validator->validate($input)) {
            return $this->jsonInvalidInput($errors);
        }

        $this->projectManager->update($project, $input);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    #[IsGranted('tracking.projects.delete')]
    public function delete(int $id): Response
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            return $this->jsonNotFound();
        }

        $this->projectManager->delete($project);

        return $this->jsonSuccess();
    }
}
```

---

## Étape 7 — Template Twig

```twig
{# templates/Module/Tracking/admin/projects/index.html.twig #}
{% extends '@Core/backend/layout.html.twig' %}

{% block content %}
    {{ vue_component('tracking/admin/ProjectsApp') }}
{% endblock %}
```

Déclarer le namespace Twig dans `config/packages/twig.yaml` :

```yaml
twig:
    paths:
        '%kernel.project_dir%/templates/Module/Tracking': Tracking
```

---

## Étape 8 — Composant Vue

```
assets/client/Module/Tracking/admin/ProjectsApp.vue
```

Voir les composants existants dans `assets/client/Module/Tracking/` comme référence.
Utiliser les composants `App*` de `@shared/components/` (jamais `<button>`, `<input>` bruts).

---

## Étape 9 — Translations

```yaml
# src/Module/Tracking/translations/messages.fr.yaml
admin:
  nav:
    tracking: Suivi
    tracking.projects: Projets
  tracking:
    projects:
      title: Projets
      create: Nouveau projet
      # …
```

Enregistrer le chemin dans `config/services.yaml` :

```yaml
App\Core\Command\DumpJsTranslationsCommand:
    arguments:
        $sourceDirs:
            - '%kernel.project_dir%/src/Module/Tracking/translations'
```

Puis :

```bash
make i18n
```

---

## Checklist finale

- [ ] `TrackingModule` enregistré dans `services.yaml` avec tag `aurora.module`
- [ ] `make sf CMD="aurora:privileges:sync"` — permissions synchronisées
- [ ] `make sf CMD="aurora:menus:sync"` — menus synchronisés
- [ ] Migration générée + appliquée
- [ ] Namespace Twig déclaré dans `twig.yaml`
- [ ] Traductions enregistrées dans `services.yaml` + `make i18n` joué
- [ ] `make ft` vert
