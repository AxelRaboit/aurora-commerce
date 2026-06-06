<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\DataFixtures;

use Aurora\Core\DataFixtures\CoreDemoFixtures;
use Aurora\Module\Notes\Markdown\Entity\AbstractMarkdownNote;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNote;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNoteInterface;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use ReflectionProperty;

use function assert;

/**
 * Demo markdown notebook (Welcome / Getting Started / Tasks / Random thoughts,
 * with wiki-links) on the first demo user from {@see CoreDemoFixtures}.
 * Dev/test only.
 */
class NotesDemoFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function getDependencies(): array
    {
        return [CoreDemoFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);

        $owner = $this->getReference(CoreDemoFixtures::userRef(0), User::class);

        /** @var class-string<MarkdownNote> $noteClass */
        $noteClass = $manager->getClassMetadata(MarkdownNoteInterface::class)->getName();

        $agency = $owner->getAgency();
        $now = new DateTimeImmutable();

        $defs = [
            [
                'title' => 'Welcome',
                'tags' => ['demo', 'guide'],
                'content' => <<<MD
                    # Welcome

                    This is your demo notebook — a small showcase of the **Markdown** notes
                    sub-module ported from Onyx.

                    Browse the tree on the left, or jump to:
                    - [[Getting Started]] — markdown syntax reference
                    - [[Tasks]] — a sample checklist
                    - [[Random thoughts]] — quick capture

                    > Try renaming this note: every `[[Welcome]]` link in your other notes
                    > will update automatically.
                    MD,
                'parent' => null,
            ],
            [
                'title' => 'Getting Started',
                'tags' => ['demo', 'guide'],
                'content' => <<<MD
                    # Getting Started

                    A quick taste of the supported syntax.

                    ## Inline formatting

                    **bold**, *italic*, ~~strikethrough~~, `inline code`, [external link](https://example.com).

                    ## Lists

                    - apples
                    - oranges
                      - clementines
                    - bananas

                    ## Checklist

                    - [x] Read [[Welcome]]
                    - [ ] Open [[Tasks]]
                    - [ ] Doodle in [[Random thoughts]]

                    ## Code block

                    ```php
                    function greet(string \$name): string
                    {
                        return "Hello, {\$name}!";
                    }
                    ```

                    ## Quote

                    > Wiki-links use `[[Title]]` and point to other notes by title (case-insensitive).
                    MD,
                'parent' => 'Welcome',
            ],
            [
                'title' => 'Tasks',
                'tags' => ['demo', 'todo'],
                'content' => <<<MD
                    # Tasks

                    A sample checklist. Backlinks pane should show [[Welcome]] and
                    [[Getting Started]] linking here.

                    - [ ] Add a new note from the sidebar `+`
                    - [ ] Move this note under [[Welcome]] (drag & drop)
                    - [ ] Open the graph view to see the wiki-link web
                    - [x] Read the intro
                    MD,
                'parent' => null,
            ],
            [
                'title' => 'Random thoughts',
                'tags' => ['demo'],
                'content' => <<<MD
                    # Random thoughts

                    Whatever comes to mind. No structure required.

                    Today's todo: revisit [[Tasks]] tonight.
                    MD,
                'parent' => null,
            ],
        ];

        $byTitle = [];
        foreach ($defs as $i => $def) {
            $note = new $noteClass();
            $note->setUser($owner);
            $note->setAgency($agency);
            $note->setTitle($def['title']);
            $note->setContent($def['content']);
            $note->setTags($def['tags']);
            $note->setPosition($i);

            if (null !== $def['parent'] && isset($byTitle[$def['parent']])) {
                $note->setParent($byTitle[$def['parent']]);
            }

            // Lifecycle callbacks don't fire for direct persist + manual flush
            // in the same operation reliably across all Doctrine versions when
            // touching MappedSuperclass + trait properties — set explicitly.
            new ReflectionProperty(AbstractMarkdownNote::class, 'createdAt')->setValue($note, $now);
            new ReflectionProperty(AbstractMarkdownNote::class, 'updatedAt')->setValue($note, $now);

            $manager->persist($note);
            $byTitle[$def['title']] = $note;
        }

        $manager->flush();
    }
}
