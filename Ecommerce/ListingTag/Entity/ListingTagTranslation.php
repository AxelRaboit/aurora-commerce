<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'core_ecommerce_listing_tag_translations')]
#[ORM\UniqueConstraint(name: 'uniq_listing_tag_translation_locale', columns: ['tag_id', 'locale'])]
#[ORM\UniqueConstraint(name: 'uniq_listing_tag_translation_slug', columns: ['locale', 'slug'])]
class ListingTagTranslation extends AbstractListingTagTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_listing_tag_translation_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
