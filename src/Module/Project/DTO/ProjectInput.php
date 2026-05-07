<?php

declare(strict_types=1);

namespace Aurora\Module\Project\DTO;

use Aurora\Core\Support\Str;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProjectInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.projects.errors.title_required')]
        #[Assert\Length(max: 255)]
        public string $title = '',
        public ?string $description = null,
        #[Assert\NotBlank(message: 'backend.projects.errors.status_required')]
        #[Assert\Choice(callback: [ProjectStatusEnum::class, 'values'], message: 'backend.projects.errors.status_invalid')]
        public string $status = ProjectStatusEnum::Draft->value,
        public ?string $startDate = null,
        public ?string $endDate = null,
        #[Assert\Positive]
        public ?int $responsibleUserId = null,
        /** @var list<int> */
        #[Assert\All([new Assert\Positive()])]
        public array $crmContactIds = [],
        #[Assert\Positive]
        public ?int $crmCompanyId = null,
    ) {}

    public function statusEnum(): ProjectStatusEnum
    {
        return ProjectStatusEnum::from($this->status);
    }

    /**
     * @return list<int>
     */
    private static function normalizeIdList(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            if (null === $value) {
                continue;
            }

            if ('' === $value) {
                continue;
            }

            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: Str::trimFromArray($data, 'title'),
            description: Str::trimOrNullFromArray($data, 'description'),
            status: isset($data['status']) && '' !== $data['status']
                ? (string) $data['status']
                : ProjectStatusEnum::Draft->value,
            startDate: Str::trimOrNullFromArray($data, 'startDate'),
            endDate: Str::trimOrNullFromArray($data, 'endDate'),
            responsibleUserId: isset($data['responsibleUserId']) && '' !== (string) $data['responsibleUserId'] ? (int) $data['responsibleUserId'] : null,
            crmContactIds: self::normalizeIdList($data['crmContactIds'] ?? []),
            crmCompanyId: isset($data['crmCompanyId']) && '' !== (string) $data['crmCompanyId'] ? (int) $data['crmCompanyId'] : null,
        );
    }
}
