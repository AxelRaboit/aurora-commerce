<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration;

use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;

final class TaxonomyMigrationTest extends IntegrationTestCase
{
    public function testBuiltInTaxonomiesExistAfterMigration(): void
    {
        $repository = static::getContainer()->get(TaxonomyRepository::class);

        $tag = $repository->findOneBySlug('tag');
        self::assertNotNull($tag);
        self::assertFalse($tag->isHierarchical());
        self::assertTrue($tag->isBuiltIn());

        $category = $repository->findOneBySlug('category');
        self::assertNotNull($category);
        self::assertTrue($category->isHierarchical());
        self::assertTrue($category->isBuiltIn());
    }

    public function testBuiltInTaxonomiesHaveTranslationsForAllEnabledLocales(): void
    {
        $repository = static::getContainer()->get(TaxonomyRepository::class);
        $enabledLocales = static::getContainer()->getParameter('kernel.enabled_locales');

        foreach (['tag', 'category'] as $slug) {
            $taxonomy = $repository->findOneBySlug($slug);
            self::assertNotNull($taxonomy, sprintf('Taxonomy "%s" should exist', $slug));

            foreach ($enabledLocales as $locale) {
                self::assertNotNull(
                    $taxonomy->getTranslation($locale),
                    sprintf('Taxonomy "%s" should have a "%s" translation', $slug, $locale),
                );
            }
        }
    }

    public function testLegacyTagsWereMigratedToTermsWithTranslationsPerLocale(): void
    {
        $taxonomies = static::getContainer()->get(TaxonomyRepository::class);
        $terms = static::getContainer()->get(TaxonomyTermRepository::class);

        $tag = $taxonomies->findOneBySlug('tag');
        $migrated = $terms->findBy(['taxonomy' => $tag]);
        self::assertNotEmpty($migrated, 'Legacy tags should have been migrated to terms');

        $enabledLocales = static::getContainer()->getParameter('kernel.enabled_locales');
        foreach ($migrated as $term) {
            foreach ($enabledLocales as $locale) {
                $translation = $term->getTranslation($locale);
                self::assertNotNull($translation, 'Each migrated term should have a translation per enabled locale');
                self::assertNotSame('', $translation->getName());
                self::assertNotSame('', $translation->getSlug());
            }
        }
    }
}
