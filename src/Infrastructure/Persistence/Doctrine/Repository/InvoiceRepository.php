<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Invoice;
use App\Domain\Repository\InvoiceRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\InvoiceOrm;
use Doctrine\ORM\EntityManagerInterface;

final class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Invoice $invoice): void
    {
        $existing = $this->entityManager->getRepository(InvoiceOrm::class)->find($invoice->id());

        if ($existing instanceof InvoiceOrm) {
            $existing->setEstadoHolded($invoice->estadoHolded());
            $existing->setPdfPath($invoice->pdfPath());
        } else {
            $this->entityManager->persist($this->domainToOrm($invoice));
        }

        $this->entityManager->flush();
    }

    public function findById(string $id): ?Invoice
    {
        $orm = $this->entityManager->getRepository(InvoiceOrm::class)->find($id);

        return $orm instanceof InvoiceOrm ? $this->ormToDomain($orm) : null;
    }

    /**
     * @return Invoice[]
     */
    public function findByExpedienteId(string $expedienteId): array
    {
        $orms = $this->entityManager->getRepository(InvoiceOrm::class)->findBy(
            ['expedienteId' => $expedienteId],
            ['createdAt' => 'DESC'],
        );

        return array_map($this->ormToDomain(...), $orms);
    }

    /**
     * @return Invoice[]
     */
    public function findAll(): array
    {
        $orms = $this->entityManager->getRepository(InvoiceOrm::class)->findBy([], ['createdAt' => 'DESC']);

        return array_map($this->ormToDomain(...), $orms);
    }

    private function ormToDomain(InvoiceOrm $orm): Invoice
    {
        return new Invoice(
            $orm->getId(),
            $orm->getExpedienteId(),
            $orm->getHoldedId(),
            $orm->getNumero(),
            $orm->getConcepto(),
            $orm->getModalidad(),
            $orm->getFecha(),
            $orm->getImporte(),
            $orm->getEstadoHolded(),
            $orm->getPdfPath(),
            $orm->getCreatedAt(),
        );
    }

    private function domainToOrm(Invoice $invoice): InvoiceOrm
    {
        $orm = new InvoiceOrm();
        $orm->setId($invoice->id());
        $orm->setExpedienteId($invoice->expedienteId());
        $orm->setHoldedId($invoice->holdedId());
        $orm->setNumero($invoice->numero());
        $orm->setConcepto($invoice->concepto());
        $orm->setModalidad($invoice->modalidad());
        $orm->setFecha($invoice->fecha());
        $orm->setImporte($invoice->importe());
        $orm->setEstadoHolded($invoice->estadoHolded());
        $orm->setPdfPath($invoice->pdfPath());
        $orm->setCreatedAt($invoice->createdAt());

        return $orm;
    }
}
