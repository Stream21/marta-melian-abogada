<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Invoice;

interface InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): void;

    public function findById(string $id): ?Invoice;

    /**
     * @return Invoice[]
     */
    public function findByExpedienteId(string $expedienteId): array;

    /**
     * @return Invoice[]
     */
    public function findAll(): array;
}
