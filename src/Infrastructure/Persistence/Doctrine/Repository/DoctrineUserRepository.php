<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\DoctrineUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DoctrineUser>
 */
class DoctrineUserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DoctrineUser::class);
    }

    public function findByEmail(string $email): ?User
    {
        $orm = $this->findOneBy(['email' => $email]);

        if ($orm === null) {
            return null;
        }

        return $this->toDomain($orm);
    }

    public function save(User $user): void
    {
        $orm = $this->findOneBy(['id' => $user->getId()]);

        if ($orm === null) {
            $orm = new DoctrineUser();
            $orm->setId($user->getId());
        }

        $orm->setEmail($user->getEmail());
        $orm->setPassword($user->getPasswordHash());
        $orm->setRoles($user->getRoles());

        $this->getEntityManager()->persist($orm);
        $this->getEntityManager()->flush();
    }

    public function emailExists(string $email): bool
    {
        return $this->findOneBy(['email' => $email]) !== null;
    }

    private function toDomain(DoctrineUser $orm): User
    {
        return new User(
            id: $orm->getId(),
            email: $orm->getEmail(),
            passwordHash: $orm->getPassword(),
            roles: $orm->getRoles(),
        );
    }
}
