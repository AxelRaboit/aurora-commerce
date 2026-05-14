<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTag;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
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
    public function findAllOrdered(?string $locale = null): array
    {
        /** @var list<ListingTagInterface> $tags */
        $tags = $this->createQueryBuilder('t')
            ->leftJoin('t.translations', 'tr')
            ->addSelect('tr')
            ->getQuery()
            ->getResult();

        $resolveName = static function (ListingTagInterface $tag) use ($locale): string {
            if (null !== $locale) {
                $translation = $tag->getTranslation($locale);
                if (null !== $translation) {
                    return strtolower((string) $translation->getName());
                }
            }
            foreach ($tag->getTranslations() as $translation) {
                return strtolower((string) $translation->getName());
            }

            return '';
        };

        usort($tags, static fn (ListingTagInterface $a, ListingTagInterface $b): int => strcmp($resolveName($a), $resolveName($b)));

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
