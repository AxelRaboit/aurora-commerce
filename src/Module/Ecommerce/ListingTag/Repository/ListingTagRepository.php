<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTag;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<ListingTagInterface>
 */
class ListingTagRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListingTag::class, ListingTagInterface::class);
    }

    /** @return list<ListingTagInterface> */
    public function findAllOrdered(): array
    {
        /** @var list<ListingTagInterface> $tags */
        $tags = $this->createQueryBuilder('t')
            ->leftJoin('t.translations', 'tr')
            ->addSelect('tr')
            ->getQuery()
            ->getResult();

        usort($tags, static function (ListingTagInterface $a, ListingTagInterface $b): int {
            $aName = '';
            foreach ($a->getTranslations() as $translation) {
                $aName = $translation->getName();
                break;
            }
            $bName = '';
            foreach ($b->getTranslations() as $translation) {
                $bName = $translation->getName();
                break;
            }

            return strcasecmp($aName, $bName);
        });

        return $tags;
    }

    public function findOneBySlug(string $slug, string $locale): ?ListingTagInterface
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.translations', 'tr')
            ->andWhere('tr.locale = :locale')
            ->andWhere('tr.slug = :slug')
            ->setParameter('locale', $locale)
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
