<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Cliente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Infrastructure\Persistence\Doctrine\Entity\ClienteOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ClienteRepository implements ClienteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Cliente $cliente): void
    {
        $existing = $this->entityManager->getRepository(ClienteOrm::class)->find($cliente->id()->value());
        $now = new \DateTimeImmutable();

        if ($existing instanceof ClienteOrm) {
            $this->applyDomainToOrm($existing, $cliente, $now);
        } else {
            $orm = new ClienteOrm();
            $orm->setId($cliente->id()->value());
            $orm->setCreatedAt($now);
            $this->applyDomainToOrm($orm, $cliente, $now);
            $this->entityManager->persist($orm);
        }

        $this->entityManager->flush();
    }

    public function findById(ClienteId $id): ?Cliente
    {
        $orm = $this->entityManager->getRepository(ClienteOrm::class)->find($id->value());

        return $orm instanceof ClienteOrm ? $this->ormToDomain($orm) : null;
    }

    public function findByTelefono(string $telefono): ?Cliente
    {
        $orm = $this->entityManager->getRepository(ClienteOrm::class)->findOneBy(['telefono' => $telefono]);

        return $orm instanceof ClienteOrm ? $this->ormToDomain($orm) : null;
    }

    /**
     * @return Cliente[]
     */
    public function search(string $query, int $limit = 20): array
    {
        $trimmed = trim($query);
        if ('' === $trimmed) {
            return [];
        }

        $like = '%' . addcslashes(mb_strtolower($trimmed), '%_\\') . '%';
        $normalizedPhone = $this->normalizeTelefono($trimmed);

        $conditions = [
            'LOWER(c.nombre) LIKE :like',
            'LOWER(c.email) LIKE :like',
            'LOWER(c.numDocumento) LIKE :like',
            'LOWER(c.telefono) LIKE :like',
        ];
        if (null !== $normalizedPhone) {
            $conditions[] = 'c.telefono = :phone';
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
            ->from(ClienteOrm::class, 'c')
            ->where($qb->expr()->orX(...$conditions))
            ->setParameter('like', $like)
            ->orderBy('c.nombre', 'ASC')
            ->setMaxResults($limit);

        if (null !== $normalizedPhone) {
            $qb->setParameter('phone', $normalizedPhone);
        }

        /** @var ClienteOrm[] $orms */
        $orms = $qb->getQuery()->getResult();

        return array_map($this->ormToDomain(...), $orms);
    }

    /**
     * @return Cliente[]
     */
    public function findAll(): array
    {
        $orms = $this->entityManager->getRepository(ClienteOrm::class)->findBy([], ['nombre' => 'ASC']);

        return array_map($this->ormToDomain(...), $orms);
    }

    private function normalizeTelefono(string $telefono): ?string
    {
        $collapsed = preg_replace('/\s+/', '', trim($telefono));

        return ('' === $collapsed || null === $collapsed) ? null : $collapsed;
    }

    private function applyDomainToOrm(ClienteOrm $orm, Cliente $cliente, \DateTimeImmutable $now): void
    {
        $orm->setNombre($cliente->nombre());
        $orm->setNacionalidad($cliente->nacionalidad());
        $orm->setTipoDocumento($cliente->tipoDocumento());
        $orm->setNumDocumento($cliente->numDocumento());
        $orm->setFechaNacimiento($cliente->fechaNacimiento());
        $orm->setLugarNacimiento($cliente->lugarNacimiento());
        $orm->setDomicilio($cliente->domicilio());
        $orm->setCodigoPostal($cliente->codigoPostal());
        $orm->setCiudad($cliente->ciudad());
        $telefono = $cliente->telefono();
        $orm->setTelefono('' === $telefono ? null : $telefono);
        $orm->setEmail($cliente->email());
        $orm->setUpdatedAt($now);
    }

    private function ormToDomain(ClienteOrm $orm): Cliente
    {
        return new Cliente(
            new ClienteId($orm->getId()),
            $orm->getNombre(),
            $orm->getNacionalidad(),
            $orm->getTipoDocumento(),
            $orm->getNumDocumento(),
            $orm->getFechaNacimiento(),
            $orm->getLugarNacimiento(),
            $orm->getDomicilio(),
            $orm->getCodigoPostal(),
            $orm->getCiudad(),
            $orm->getTelefono() ?? '',
            $orm->getEmail(),
            $orm->getCreatedAt(),
            $orm->getUpdatedAt(),
        );
    }
}
