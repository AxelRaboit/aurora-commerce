<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'core_ecommerce_listing_category_translations')]
#[ORM\UniqueConstraint(name: 'uniq_listing_category_translation_locale', columns: ['category_id', 'locale'])]
#[ORM\UniqueConstraint(name: 'uniq_listing_category_translation_slug', columns: ['locale', 'slug'])]
class ListingCategoryTranslation extends AbstractListingCategoryTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_listing_category_translation_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
