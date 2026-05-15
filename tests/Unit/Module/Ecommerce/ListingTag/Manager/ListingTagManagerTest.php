<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\ListingTag\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Core\Locale\Service\TranslationLocaleSyncer;
use Aurora\Module\Ecommerce\ListingTag\Dto\ListingTagInput;
use Aurora\Module\Ecommerce\ListingTag\Dto\ListingTagTranslationInput;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTag;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Ecommerce\ListingTag\Manager\ListingTagManager;
use Aurora\Module\Ecommerce\ListingTag\Repository\ListingTagRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AllowMockObjectsWithoutExpectations]
final class ListingTagManagerTest extends TestCase
{
    private function makeManager(?EntityManagerInterface $entityManager = null): ListingTagManager
    {
        $localeContext = $this->createMock(LocaleContextInterface::class);
        $localeContext->method('getActiveLocales')->willReturn(['fr', 'en']);

        return new ListingTagManager(
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $this->createMock(ListingTagRepository::class),
            $this->createMock(AuditLogger::class),
            new AsciiSlugger(),
            new TranslationLocaleSyncer($localeContext),
        );
    }

    public function testCreateInstantiatesTagAndAppliesAllFields(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $manager = $this->makeManager($entityManager);

        $input = new ListingTagInput(
            color: '#ABCDEF',
            isVisible: false,
            translations: [
                'en' => new ListingTagTranslationInput('Promo', null, 'Promotional tag'),
            ],
        );

        $tag = $manager->create($input);

        self::assertInstanceOf(ListingTag::class, $tag);
        self::assertSame('#ABCDEF', $tag->getColor());
        self::assertFalse($tag->isVisible());
        self::assertSame(1, $tag->getTranslations()->count());

        $translation = $tag->getTranslation('en');
        self::assertNotNull($translation);
        self::assertSame('Promo', $translation->getName());
        self::assertSame('promo', $translation->getSlug());
        self::assertSame('Promotional tag', $translation->getDescription());
    }

    public function testUpdateMutatesExistingTag(): void
    {
        $manager = $this->makeManager();
        $existing = new ListingTag();
        $existing->setColor('#000000');
        $existing->setVisible(true);

        $input = new ListingTagInput(
            color: '#FFFFFF',
            isVisible: false,
            translations: [
                'en' => new ListingTagTranslationInput('Updated', 'updated-slug', null),
            ],
        );

        $manager->update($existing, $input);

        self::assertSame('#FFFFFF', $existing->getColor());
        self::assertFalse($existing->isVisible());
        self::assertSame('Updated', $existing->getTranslation('en')?->getName());
        self::assertSame('updated-slug', $existing->getTranslation('en')?->getSlug());
    }

    public function testSlugIsAutoDerivedWhenInputSlugIsEmpty(): void
    {
        $manager = $this->makeManager();
        $input = new ListingTagInput(
            color: '#112233',
            isVisible: true,
            translations: [
                'en' => new ListingTagTranslationInput('Hello World', null, null),
            ],
        );

        $tag = $manager->create($input);

        self::assertSame('hello-world', $tag->getTranslation('en')?->getSlug());
    }

    public function testTranslationCycleAddRemoveUpdate(): void
    {
        $manager = $this->makeManager();

        $input = new ListingTagInput(
            color: '#112233',
            isVisible: true,
            translations: [
                'en' => new ListingTagTranslationInput('English', null, null),
                'fr' => new ListingTagTranslationInput('Francais', null, null),
            ],
        );

        $tag = $manager->create($input);
        self::assertSame(2, $tag->getTranslations()->count());

        // Update: keep en (rename), drop fr
        $updateInput = new ListingTagInput(
            color: '#112233',
            isVisible: true,
            translations: [
                'en' => new ListingTagTranslationInput('EnglishV2', null, null),
            ],
        );
        $manager->update($tag, $updateInput);

        self::assertSame(1, $tag->getTranslations()->count());
        self::assertSame('EnglishV2', $tag->getTranslation('en')?->getName());
        self::assertNull($tag->getTranslation('fr'));
    }

    public function testDeleteRemovesTheTag(): void
    {
        $tag = $this->createMock(ListingTagInterface::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($tag);
        $entityManager->expects(self::once())->method('flush');

        $manager = $this->makeManager($entityManager);
        $manager->delete($tag);
    }
}
