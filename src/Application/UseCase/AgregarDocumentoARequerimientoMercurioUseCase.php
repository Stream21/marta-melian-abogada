<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ExpedienteRequerimientoDocumento;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\ResponsableRequerimientoDocumento;
use App\Domain\Repository\ExpedienteRequerimientoDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoDocumentoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final class AgregarDocumentoARequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ExpedienteRequerimientoDocumentoRepositoryInterface $documentoRepository,
    ) {
    }

    public function __invoke(
        string $expedienteId,
        string $requerimientoId,
        string $nombre,
        string $responsable = 'abogado',
        string $descripcion = '',
        bool $obligatorio = true,
        int $numeroArchivos = 1,
    ): string {
        $expId = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($expId);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de tramitación.');
        }

        $reqId = new ExpedienteRequerimientoMercurioId($requerimientoId);
        $req = $this->requerimientoRepository->findById($reqId);
        if (null === $req || !$req->expedienteId()->equals($expId)) {
            throw new \InvalidArgumentException('Requerimiento no encontrado.');
        }
        if (!$req->estado()->estaAbierto()) {
            throw new \InvalidArgumentException('El requerimiento ya está cerrado o presentado.');
        }

        $nombre = trim($nombre);
        if ('' === $nombre) {
            throw new \InvalidArgumentException('El nombre del documento es obligatorio.');
        }

        $maxArchivos = max(1, min(20, $numeroArchivos));
        $responsableEnum = ResponsableRequerimientoDocumento::fromString($responsable);
        $descripcion = trim($descripcion);

        $existentes = $this->documentoRepository->findByRequerimientoId($reqId);
        $orden = count($existentes);

        $documento = ExpedienteRequerimientoDocumento::crear(
            ExpedienteRequerimientoDocumentoId::generate(),
            $reqId,
            $nombre,
            $descripcion,
            $responsableEnum,
            $obligatorio,
            $orden,
            $maxArchivos,
        );

        $this->documentoRepository->save($documento);

        if (ResponsableRequerimientoDocumento::Cliente === $responsableEnum) {
            $req = $req->abrirParaCliente();
            $this->requerimientoRepository->save($req);
        }

        return $documento->id()->value();
    }
}
