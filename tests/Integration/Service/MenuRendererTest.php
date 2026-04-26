<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Post;
use App\Entity\TaxonomyTerm;
use App\Entity\User;
use App\Enum\MenuItemTargetTypeEnum;
use App\Enum\MenuItemVisibilityEnum;
use App\Manager\MenuManager;
use App\Repository\MenuRepository;
use App\Repository\PostRepository;
use App\Repository\TaxonomyTermRepository;
use App\Service\MenuRenderer;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class MenuRendererTest extends IntegrationTestCase
{
    private MenuManager $manager;
    private MenuRenderer $renderer;
    private MenuRepository $menuRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        // createClient() is required to set up the security firewall context
        // (Security::getUser() must work even when no user is logged in).
        static::createClient();
        $this->manager = static::getContainer()->get(MenuManager::class);
        $this->renderer = static::getContainer()->get(MenuRenderer::class);
        $this->menuRepository = static::getContainer()->get(MenuRepository::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Clean menus between tests
        foreach ($this->menuRepository->findAll() as $menu) {
            $this->entityManager->remove($menu);
        }
        $this->entityManager->flush();
    }

    private function publishedPost(): Post
    {
        $post = static::getContainer()->get(PostRepository::class)
            ->findOneBy([], ['id' => 'ASC']);
        self::assertInstanceOf(Post::class, $post);

        return $post;
    }

    private function firstTerm(): TaxonomyTerm
    {
        $term = static::getContainer()->get(TaxonomyTermRepository::class)
            ->findOneBy([], ['id' => 'ASC']);
        self::assertInstanceOf(TaxonomyTerm::class, $term);

        return $term;
    }

    public function testRenderEmptyForUnknownLocation(): void
    {
        self::assertSame([], $this->renderer->render('does-not-exist', 'fr'));
    }

    public function testRenderHomeAndCustomUrlItems(): void
    {
        $menu = $this->manager->createMenu('Header', 'primary');
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::CustomUrl, null, [
            'customUrl' => 'https://example.com',
            'openInNewTab' => true,
            'cssClass' => 'btn',
        ]);
        // Translation override required for CustomUrl
        $custom = $menu->getItems()->last();
        $this->manager->setTranslation($custom, 'fr', 'Exemple');

        $tree = $this->renderer->render('primary', 'fr');

        self::assertCount(2, $tree);
        self::assertSame('Accueil', $tree[0]['label']);
        self::assertSame('/fr', $tree[0]['url']);
        self::assertFalse($tree[0]['openInNewTab']);

        self::assertSame('Exemple', $tree[1]['label']);
        self::assertSame('https://example.com', $tree[1]['url']);
        self::assertTrue($tree[1]['openInNewTab']);
        self::assertSame('btn', $tree[1]['cssClass']);
    }

    public function testCustomUrlWithoutTranslationIsDropped(): void
    {
        $menu = $this->manager->createMenu('Header', 'primary');
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::CustomUrl, null, [
            'customUrl' => 'https://example.com',
        ]);

        $tree = $this->renderer->render('primary', 'fr');

        self::assertSame([], $tree);
    }

    public function testTranslationOverridesNaturalLabel(): void
    {
        $menu = $this->manager->createMenu('Header', 'primary');
        $item = $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);
        $this->manager->setTranslation($item, 'fr', 'Maison');

        $tree = $this->renderer->render('primary', 'fr');

        self::assertSame('Maison', $tree[0]['label']);
    }

    public function testRenderPostItem(): void
    {
        $post = $this->publishedPost();
        $menu = $this->manager->createMenu('Header', 'primary');
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::Post, $post->getId());

        $tree = $this->renderer->render('primary', 'fr');

        self::assertCount(1, $tree);
        self::assertNotEmpty($tree[0]['label']);
        self::assertStringStartsWith('/fr/'.$post->getPostType()->getSlug().'/', $tree[0]['url']);
    }

    public function testRenderTermItem(): void
    {
        $term = $this->firstTerm();
        $menu = $this->manager->createMenu('Header', 'primary');
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::Term, $term->getId());

        $tree = $this->renderer->render('primary', 'fr');

        self::assertCount(1, $tree);
        self::assertNotEmpty($tree[0]['url']);
        self::assertStringStartsWith('/fr/', $tree[0]['url']);
    }

    public function testPostItemDroppedWhenTargetMissing(): void
    {
        $menu = $this->manager->createMenu('Header', 'primary');
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::Post, 999999);

        $tree = $this->renderer->render('primary', 'fr');

        self::assertSame([], $tree);
    }

    public function testGuestsOnlyVisibleWhenAnonymous(): void
    {
        $menu = $this->manager->createMenu('Header', 'primary');
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontLogin, null, [
            'visibility' => MenuItemVisibilityEnum::GuestsOnly,
        ]);
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);

        $tree = $this->renderer->render('primary', 'fr');
        self::assertCount(2, $tree);
    }

    public function testAuthenticatedOnlyHiddenWhenAnonymous(): void
    {
        $menu = $this->manager->createMenu('Header', 'primary');
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontAccount, null, [
            'visibility' => MenuItemVisibilityEnum::AuthenticatedOnly,
        ]);
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);

        // Anonymous: only Home should remain (account is hidden)
        $tree = $this->renderer->render('primary', 'fr');
        self::assertCount(1, $tree);
        self::assertSame('Accueil', $tree[0]['label']);
    }

    public function testNestedItemsRenderTree(): void
    {
        $menu = $this->manager->createMenu('Header', 'primary');
        $parent = $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontLogin, null, [
            'parentId' => $parent->getId(),
            'visibility' => MenuItemVisibilityEnum::GuestsOnly,
        ]);
        $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontRegister, null, [
            'parentId' => $parent->getId(),
            'visibility' => MenuItemVisibilityEnum::GuestsOnly,
        ]);

        $this->entityManager->clear();

        $tree = $this->renderer->render('primary', 'fr');

        self::assertCount(1, $tree);
        self::assertCount(2, $tree[0]['children']);
        self::assertSame('Connexion', $tree[0]['children'][0]['label']);
        self::assertSame('Inscription', $tree[0]['children'][1]['label']);
    }

    public function testItemsOrderedByPosition(): void
    {
        $menu = $this->manager->createMenu('Header', 'primary');
        $a = $this->manager->createItem($menu, MenuItemTargetTypeEnum::Home);
        $b = $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontLogin);
        $c = $this->manager->createItem($menu, MenuItemTargetTypeEnum::FrontRegister);

        $this->manager->reorderItems($menu, [
            ['id' => $c->getId(), 'parentId' => null, 'position' => 0],
            ['id' => $a->getId(), 'parentId' => null, 'position' => 1],
            ['id' => $b->getId(), 'parentId' => null, 'position' => 2],
        ]);

        $tree = $this->renderer->render('primary', 'fr');

        self::assertSame('Inscription', $tree[0]['label']);
        self::assertSame('Accueil', $tree[1]['label']);
        self::assertSame('Connexion', $tree[2]['label']);
    }
}
