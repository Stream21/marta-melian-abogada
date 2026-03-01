<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\DoctrineUser;

#[AsCommand(
    name: 'app:create-user',
    description: 'Crea un usuario en la base de datos.',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email del usuario')
            ->addArgument('password', InputArgument::REQUIRED, 'Contraseña en texto plano')
            ->addArgument('role', InputArgument::OPTIONAL, 'Rol del usuario (ROLE_ADMIN o ROLE_USER)', 'ROLE_USER');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');
        $role = strtoupper($input->getArgument('role'));

        if (!in_array($role, ['ROLE_ADMIN', 'ROLE_USER'], true)) {
            $io->error(sprintf('Rol inválido: "%s". Usa ROLE_ADMIN o ROLE_USER.', $role));
            return Command::FAILURE;
        }

        if ($this->userRepository->emailExists($email)) {
            $io->error(sprintf('Ya existe un usuario con el email "%s".', $email));
            return Command::FAILURE;
        }

        $doctrineUser = new DoctrineUser();
        $hashedPassword = $this->passwordHasher->hashPassword($doctrineUser, $plainPassword);

        $user = new User(
            id: Uuid::uuid4()->toString(),
            email: $email,
            passwordHash: $hashedPassword,
            roles: [$role],
        );

        $this->userRepository->save($user);

        $io->success(sprintf('Usuario "%s" creado con rol %s.', $email, $role));

        return Command::SUCCESS;
    }
}
