<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ExpedienteFirmaDocumento;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\ExpedienteFirmaRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteFirmaDocumentoOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteFirmaRepository implements ExpedienteFirmaRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ExpedienteFirmaDocumento $firma): void
    {
        $existing = $this->entityManager->getRepository(ExpedienteFirmaDocumentoOrm::class)->findOneBy([
            'expedienteId' => $firma->expedienteId()->value(),
            'tipoEscrito' => $firma->tipoEscrito()->value,
        ]);

        if ($existing instanceof ExpedienteFirmaDocumentoOrm) {
            $existing->setFirmaPngPath($firma->firmaPngPath());
            $existing->setPdfFirmadoPath($firma->pdfFirmadoPath());
            $existing->setFirmadoAt($firma->firmadoAt());
            $existing->setUserAgent($firma->userAgent());
            $existing->setClienteIp($firma->clienteIp());
            $existing->setPdfFirmadoSha256($firma->pdfFirmadoSha256());
        } else {
            $orm = new ExpedienteFirmaDocumentoOrm();
            $orm->setId($firma->id());
            $orm->setExpedienteId($firma->expedienteId()->value());
            $orm->setTipoEscrito($firma->tipoEscrito()->value);
            $orm->setFirmaPngPath($firma->firmaPngPath());
            $orm->setPdfFirmadoPath($firma->pdfFirmadoPath());
            $orm->setFirmadoAt($firma->firmadoAt());
            $orm->setUserAgent($firma->userAgent());
            $orm->setClienteIp($firma->clienteIp());
            $orm->setPdfFirmadoSha256($firma->pdfFirmadoSha256());
            $this->entityManager->persist($orm);
        }

        $this->entityManager->flush();
    }

    public function findByExpediente(ExpedienteId $expedienteId): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteFirmaDocumentoOrm::class)->findBy([
            'expedienteId' => $expedienteId->value(),
        ]);

        return array_map($this->ormToDomain(...), $orms);
    }

    public function findByExpedienteAndTipo(ExpedienteId $expedienteId, TipoEscrito $tipo): ?ExpedienteFirmaDocumento
    {
        $orm = $this->entityManager->getRepository(ExpedienteFirmaDocumentoOrm::class)->findOneBy([
            'expedienteId' => $expedienteId->value(),
            'tipoEscrito' => $tipo->value,
        ]);

        return $orm instanceof ExpedienteFirmaDocumentoOrm ? $this->ormToDomain($orm) : null;
    }

    private function ormToDomain(ExpedienteFirmaDocumentoOrm $orm): ExpedienteFirmaDocumento
    {
        return new ExpedienteFirmaDocumento(
            $orm->getId(),
            new ExpedienteId($orm->getExpedienteId()),
            TipoEscrito::fromString($orm->getTipoEscrito()),
            $orm->getFirmaPngPath(),
            $orm->getPdfFirmadoPath(),
            $orm->getFirmadoAt(),
            $orm->getUserAgent(),
            $orm->getClienteIp(),
            $orm->getPdfFirmadoSha256(),
        );
    }
}
