<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Cliente;
use App\Domain\ValueObject\ClienteId;

interface ClienteRepositoryInterface
{
    public function save(Cliente $cliente): void;

    public function findById(ClienteId $id): ?Cliente;

    public function findByTelefono(string $telefono): ?Cliente;

    public function findByNumDocumento(string $numDocumento): ?Cliente;

    /**
     * @return Cliente[]
     */
    public function search(string $query, int $limit = 20): array;

    /**
     * @return Cliente[]
     */
    public function findAll(): array;

    public function delete(ClienteId $id): void;
}
