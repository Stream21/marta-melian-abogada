<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Application\Service\TelefonoNormalizer;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\ClienteHoldedEstado;
use App\Domain\Entity\TipoEscaneoDocumentoIdentidad;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Infrastructure\Persistence\Doctrine\Entity\ClienteOrm;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class ClienteRepository implements ClienteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TelefonoNormalizer $telefonoNormalizer,
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

        try {
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $isUnique = $e instanceof UniqueConstraintViolationException
                || $e->getPrevious() instanceof UniqueConstraintViolationException;
            if (!$isUnique) {
                throw $e;
            }

            // Evitar estado inconsistente del Unit of Work tras el fallo.
            $this->entityManager->clear();

            throw new \InvalidArgumentException(
                'No se pudo guardar el cliente porque el teléfono ya está asignado a otro. '
                . 'Confirme continuar con el cliente repetido o use el cliente existente.',
                0,
                $e,
            );
        }
    }

    public function findById(ClienteId $id): ?Cliente
    {
        $orm = $this->entityManager->getRepository(ClienteOrm::class)->find($id->value());

        return $orm instanceof ClienteOrm ? $this->ormToDomain($orm) : null;
    }

    public function findByTelefono(string $telefono): ?Cliente
    {
        $normalized = $this->telefonoNormalizer->normalize($telefono) ?? $telefono;
        $candidates = array_values(array_unique(array_filter([
            $normalized,
            $telefono,
            $this->spanishNationalFromE164($normalized),
        ], static fn (?string $v): bool => null !== $v && '' !== $v)));

        foreach ($candidates as $candidate) {
            $orm = $this->entityManager->getRepository(ClienteOrm::class)->findOneBy(['telefono' => $candidate]);
            if ($orm instanceof ClienteOrm) {
                return $this->ormToDomain($orm);
            }
        }

        return null;
    }

    private function spanishNationalFromE164(string $telefono): ?string
    {
        $digits = preg_replace('/\D/', '', $telefono) ?? '';
        if (str_starts_with($digits, '34') && 11 === strlen($digits)) {
            $national = substr($digits, 2);
            if (1 === preg_match('/^[67]\d{8}$/', $national)) {
                return $national;
            }
        }

        return null;
    }

    public function findByNumDocumento(string $numDocumento): ?Cliente
    {
        if ('' === $numDocumento) {
            return null;
        }

        $orm = $this->entityManager->getRepository(ClienteOrm::class)->findOneBy(['numDocumento' => $numDocumento]);

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
        $normalizedPhone = $this->telefonoNormalizer->normalize($trimmed);
        $phoneDigits = null !== $normalizedPhone
            ? preg_replace('/\D/', '', $normalizedPhone)
            : preg_replace('/\D/', '', $trimmed);

        $conditions = [
            'LOWER(c.nombre) LIKE :like',
            'LOWER(c.email) LIKE :like',
            'LOWER(c.numDocumento) LIKE :like',
            'LOWER(c.telefono) LIKE :like',
        ];
        if (null !== $normalizedPhone) {
            $conditions[] = 'c.telefono = :phone';
            $national = $this->spanishNationalFromE164($normalizedPhone);
            if (null !== $national) {
                $conditions[] = 'c.telefono = :phoneNational';
            }
        }
        if (null !== $phoneDigits && '' !== $phoneDigits && strlen($phoneDigits) >= 6) {
            $conditions[] = 'c.telefono LIKE :phoneDigits';
            // También buscar por móvil nacional (sin 34) si la query viene en E.164.
            if (str_starts_with($phoneDigits, '34') && strlen($phoneDigits) === 11) {
                $conditions[] = 'c.telefono LIKE :phoneNationalDigits';
            }
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
            $national = $this->spanishNationalFromE164($normalizedPhone);
            if (null !== $national) {
                $qb->setParameter('phoneNational', $national);
            }
        }
        if (null !== $phoneDigits && '' !== $phoneDigits && strlen($phoneDigits) >= 6) {
            $qb->setParameter('phoneDigits', '%' . $phoneDigits . '%');
            if (str_starts_with($phoneDigits, '34') && strlen($phoneDigits) === 11) {
                $qb->setParameter('phoneNationalDigits', '%' . substr($phoneDigits, 2) . '%');
            }
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

    public function delete(ClienteId $id): void
    {
        $orm = $this->entityManager->getRepository(ClienteOrm::class)->find($id->value());
        if (!$orm instanceof ClienteOrm) {
            return;
        }

        $this->entityManager->remove($orm);
        $this->entityManager->flush();
    }

    private function applyDomainToOrm(ClienteOrm $orm, Cliente $cliente, \DateTimeImmutable $now): void
    {
        $orm->setNombre($cliente->nombre());
        $orm->setNacionalidad($cliente->nacionalidad());
        $orm->setTipoDocumento($cliente->tipoDocumento());
        $orm->setNumDocumento($cliente->numDocumento());
        $orm->setFechaNacimiento($cliente->fechaNacimiento());
        $orm->setLugarNacimiento($cliente->lugarNacimiento());
        $orm->setEstadoCivil($cliente->estadoCivil());
        $orm->setDomicilio($cliente->domicilio());
        $orm->setCodigoPostal($cliente->codigoPostal());
        $orm->setCiudad($cliente->ciudad());
        $orm->setProvincia($cliente->provincia());
        $orm->setNombrePadre($cliente->nombrePadre());
        $orm->setNombreMadre($cliente->nombreMadre());
        $telefono = $cliente->telefono();
        $orm->setTelefono('' === $telefono ? null : $telefono);
        $orm->setEmail($cliente->email());
        $orm->setCountryCode($cliente->countryCode());
        $orm->setHoldedContactId($cliente->holdedContactId());
        $orm->setHoldedEstado($cliente->holdedEstado()->value);
        $orm->setHoldedSyncedAt($cliente->holdedSyncedAt());
        $orm->setHoldedSyncError($cliente->holdedSyncError());
        $orm->setDocumentoIdentidadTipo($cliente->documentoIdentidadTipo()?->value);
        $orm->setDocumentoIdentidadAnversoPath($cliente->documentoIdentidadAnversoPath());
        $orm->setDocumentoIdentidadReversoPath($cliente->documentoIdentidadReversoPath());
        $orm->setDocumentoIdentidadEscaneadoAt($cliente->documentoIdentidadEscaneadoAt());
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
            $orm->getEstadoCivil(),
            $orm->getDomicilio(),
            $orm->getCodigoPostal(),
            $orm->getCiudad(),
            $orm->getProvincia(),
            $orm->getNombrePadre(),
            $orm->getNombreMadre(),
            $orm->getTelefono() ?? '',
            $orm->getEmail(),
            $orm->getCountryCode() ?: 'ES',
            $orm->getCreatedAt(),
            $orm->getUpdatedAt(),
            $orm->getHoldedContactId(),
            ClienteHoldedEstado::from($orm->getHoldedEstado()),
            $orm->getHoldedSyncedAt(),
            $orm->getHoldedSyncError(),
            null !== $orm->getDocumentoIdentidadTipo()
                ? TipoEscaneoDocumentoIdentidad::from($orm->getDocumentoIdentidadTipo())
                : null,
            $orm->getDocumentoIdentidadAnversoPath(),
            $orm->getDocumentoIdentidadReversoPath(),
            $orm->getDocumentoIdentidadEscaneadoAt(),
        );
    }
}
