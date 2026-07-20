<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\OrigenDocumentoRequeridoExpediente;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class AgregarDocumentoRequerimientosUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
    ) {
    }

    public function __invoke(
        string $expedienteId,
        string $nombre,
        string $descripcion = '',
        bool $obligatorio = true,
        string $tipo = 'individual',
        int $maxImagenes = 1,
    ): string {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de requerimientos.');
        }

        $nombre = trim($nombre);
        if ('' === $nombre) {
            throw new \InvalidArgumentException('El nombre del documento es obligatorio.');
        }

        $existentes = $this->documentoRequeridoRepository->findByExpediente($id);
        $maxOrden = 0;
        foreach ($existentes as $doc) {
            $maxOrden = max($maxOrden, $doc->orden());
        }

        $docId = new ExpedienteDocumentoRequeridoId(bin2hex(random_bytes(16)));
        $this->documentoRequeridoRepository->save(new ExpedienteDocumentoRequerido(
            $docId,
            $id,
            $nombre,
            trim($descripcion),
            $obligatorio,
            TipoDocumentoRequerido::fromString($tipo),
            max(1, $maxImagenes),
            $maxOrden + 1,
            OrigenDocumentoRequeridoExpediente::Manual,
        ));

        return $docId->value();
    }
}
