<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\MediaRepository;
use App\Service\ImageVariantGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:media:rebuild-variants',
    description: 'Regenerate image variants (thumbnail/medium/large) for every image in the media library.',
)]
final class RebuildMediaVariantsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaRepository $mediaRepository,
        private readonly ImageVariantGenerator $variantGenerator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $medias = $this->mediaRepository->findAll();
        $images = array_filter($medias, static fn ($media): bool => $media->isImage());

        if ([] === $images) {
            $io->success('No image to process.');

            return Command::SUCCESS;
        }

        $io->progressStart(count($images));

        $updated = 0;
        foreach ($images as $media) {
            $this->variantGenerator->deleteVariants($media->getVariants());
            $variants = $this->variantGenerator->generate($media->getPath(), $media->getMimeType());
            $media->setVariants($variants);
            ++$updated;
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();
        $io->success(sprintf('Rebuilt variants for %d image(s).', $updated));

        return Command::SUCCESS;
    }
}
