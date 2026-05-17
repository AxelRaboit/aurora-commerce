<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Service;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Photo\Gallery\Dto\GalleryInput;
use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryInterface;
use Aurora\Module\Photo\Gallery\Manager\GalleryManager;
use Aurora\Module\Photo\Gallery\Service\GalleryWatermarkService;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[AllowMockObjectsWithoutExpectations]
final class GalleryManagerTest extends TestCase
{
    private EntityManagerInterface $em;
    private GalleryWatermarkService $watermark;
    private GalleryManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $this->watermark = new class(new Filesystem(), Path::join(sys_get_temp_dir(), 'aurora-uploads')) extends GalleryWatermarkService {
            public int $clearCalls = 0;

            public function clearCacheForGallery(GalleryInterface $gallery): void
            {
                ++$this->clearCalls;
            }
        };

        $this->manager = new GalleryManager(
            $this->em,
            $this->createStub(MediaRepository::class),
            $this->createStub(ContactRepository::class),
            new AuditLogger($this->em, $security, new SequenceGenerator($this->createStub(Connection::class)), $this->createStub(SettingRepository::class)),
            $this->watermark,
            new SequenceGenerator($this->createStub(Connection::class)),
            $this->createStub(SettingRepository::class),
        );
    }

    private function makeUser(int $id = 1): User
    {
        $user = new User();
        $r = new ReflectionProperty(User::class, 'id');
        $r->setValue($user, $id);

        return $user;
    }

    private function makeInput(array $overrides = []): GalleryInput
    {
        $defaults = [
            'title' => 'Title',
            'slug' => 'title',
            'description' => null,
            'password' => null,
            'clearPassword' => false,
            'coverMediaId' => null,
            'expiresAt' => null,
            'allowOriginals' => true,
            'allowZipDownload' => true,
            'picksRequireIdentity' => false,
            'watermarkEnabled' => false,
            'watermarkText' => null,
            'clientContactId' => null,
        ];

        return new GalleryInput(...array_replace($defaults, $overrides));
    }

    public function testCreatePersistsAndAssignsCreatedBy(): void
    {
        $user = $this->makeUser(7);
        $this->em->expects(self::atLeastOnce())->method('persist');
        $this->em->expects(self::atLeastOnce())->method('flush');

        $gallery = $this->manager->create($this->makeInput(['title' => 'Wedding', 'slug' => 'wedding']), $user);

        self::assertSame('Wedding', $gallery->getTitle());
        self::assertSame('wedding', $gallery->getSlug());
        self::assertSame($user, $gallery->getCreatedBy());
    }

    public function testCreateHashesPasswordWhenProvided(): void
    {
        $gallery = $this->manager->create(
            $this->makeInput(['password' => 'secret']),
            $this->makeUser(),
        );

        self::assertNotNull($gallery->getPasswordHash());
        self::assertTrue(password_verify('secret', (string) $gallery->getPasswordHash()));
    }

    public function testCreateLeavesPasswordNullWhenAbsent(): void
    {
        $gallery = $this->manager->create($this->makeInput(), $this->makeUser());

        self::assertNull($gallery->getPasswordHash());
    }

    public function testUpdateClearPasswordWipesHash(): void
    {
        $gallery = (new Gallery())
            ->setTitle('t')->setSlug('s')
            ->setPasswordHash(password_hash('old', PASSWORD_BCRYPT))
            ->setCreatedBy($this->makeUser());

        $this->manager->update($gallery, $this->makeInput(['clearPassword' => true]));

        self::assertNull($gallery->getPasswordHash());
    }

    public function testUpdateLeavesPasswordUntouchedWhenNotProvided(): void
    {
        $existing = password_hash('old', PASSWORD_BCRYPT);
        $gallery = (new Gallery())->setTitle('t')->setSlug('s')->setPasswordHash($existing)->setCreatedBy($this->makeUser());

        $this->manager->update($gallery, $this->makeInput());

        self::assertSame($existing, $gallery->getPasswordHash());
    }

    public function testUpdateWatermarkChangeInvalidatesCache(): void
    {
        $gallery = (new Gallery())->setTitle('t')->setSlug('s')->setCreatedBy($this->makeUser());
        $gallery->setWatermarkEnabled(true);
        $gallery->setWatermarkText('Old');

        $this->manager->update($gallery, $this->makeInput([
            'watermarkEnabled' => true,
            'watermarkText' => 'New',
        ]));

        self::assertSame(1, $this->watermark->clearCalls);
    }

    public function testUpdateWatermarkUnchangedDoesNotInvalidateCache(): void
    {
        $gallery = (new Gallery())->setTitle('t')->setSlug('s')->setCreatedBy($this->makeUser());
        $gallery->setWatermarkEnabled(true);
        $gallery->setWatermarkText('Brand');

        $this->manager->update($gallery, $this->makeInput([
            'watermarkEnabled' => true,
            'watermarkText' => 'Brand',
        ]));

        self::assertSame(0, $this->watermark->clearCalls);
    }

    public function testDeleteClearsWatermarkCacheAndRemoves(): void
    {
        $gallery = (new Gallery())->setTitle('t')->setSlug('s')->setCreatedBy($this->makeUser());

        $this->em->expects(self::atLeastOnce())->method('remove')->with($gallery);

        $this->manager->delete($gallery);

        self::assertSame(1, $this->watermark->clearCalls);
    }

    public function testApplyInputCopiesScalarFields(): void
    {
        $expires = new DateTimeImmutable('+1 day');
        $gallery = $this->manager->create($this->makeInput([
            'title' => 'X',
            'slug' => 'x',
            'description' => 'desc',
            'expiresAt' => $expires,
            'allowOriginals' => false,
            'allowZipDownload' => false,
            'picksRequireIdentity' => true,
            'watermarkEnabled' => true,
            'watermarkText' => 'wm',
        ]), $this->makeUser());

        self::assertSame('desc', $gallery->getDescription());
        self::assertSame($expires, $gallery->getExpiresAt());
        self::assertFalse($gallery->isAllowOriginals());
        self::assertFalse($gallery->isAllowZipDownload());
        self::assertTrue($gallery->isPicksRequireIdentity());
        self::assertTrue($gallery->isWatermarkEnabled());
        self::assertSame('wm', $gallery->getWatermarkText());
    }
}
