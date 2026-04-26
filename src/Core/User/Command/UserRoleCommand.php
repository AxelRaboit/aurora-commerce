<?php

declare(strict_types=1);

namespace App\Core\User\Command;

use App\Core\User\Entity\User;
use App\Core\User\Enum\UserRoleEnum;
use App\Core\User\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aurora:user:role',
    description: 'Assigne ou retire un rôle à un utilisateur identifié par son email.',
)]
class UserRoleCommand extends Command
{
    private const ASSIGNABLE_ROLES = [
        UserRoleEnum::Admin->value,
        UserRoleEnum::Dev->value,
    ];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, "Email de l'utilisateur")
            ->addArgument('role', InputArgument::REQUIRED, sprintf('Rôle à assigner (%s)', implode(', ', self::ASSIGNABLE_ROLES)))
            ->addOption('remove', null, InputOption::VALUE_NONE, 'Retirer le rôle au lieu de l\'ajouter');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $role = mb_strtoupper((string) $input->getArgument('role'));
        $remove = (bool) $input->getOption('remove');

        if (!in_array($role, self::ASSIGNABLE_ROLES, true)) {
            $symfonyStyle->error(sprintf('Rôle "%s" invalide. Rôles disponibles : %s', $role, implode(', ', self::ASSIGNABLE_ROLES)));

            return Command::FAILURE;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            $symfonyStyle->error(sprintf('Utilisateur "%s" introuvable.', $email));

            return Command::FAILURE;
        }

        $stored = array_values(array_filter(
            $user->getRoles(),
            fn (string $storedRole): bool => UserRoleEnum::User->value !== $storedRole,
        ));

        if ($remove) {
            $filteredRoles = array_values(array_filter($stored, fn (string $storedRole): bool => $storedRole !== $role));
            if ($filteredRoles === $stored) {
                $symfonyStyle->warning(sprintf('%s n\'a pas le rôle %s.', $email, $role));

                return Command::SUCCESS;
            }

            $user->setRoles($filteredRoles);
            $this->entityManager->flush();
            $symfonyStyle->success(sprintf('Rôle %s retiré de %s.', $role, $email));

            return Command::SUCCESS;
        }

        if (in_array($role, $stored, true)) {
            $symfonyStyle->warning(sprintf('%s possède déjà le rôle %s.', $email, $role));

            return Command::SUCCESS;
        }

        $stored[] = $role;
        $user->setRoles($stored);
        $this->entityManager->flush();

        $symfonyStyle->success(sprintf('Rôle %s assigné à %s.', $role, $email));

        return Command::SUCCESS;
    }
}
