<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\DestinoRequerimientoMercurio;
use App\Domain\Entity\EstadoRequerimientoDocumento;
use App\Domain\Entity\EstadoRequerimientoMercurio;
use App\Domain\Entity\ExpedienteRequerimientoMercurio;
use App\Domain\Entity\ResponsableRequerimientoDocumento;
use App\Domain\Repository\ExpedienteRequerimientoCampoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;

final class RequerimientoMercurioCompletitudService
{
    public function __construct(
        private ExpedienteRequerimientoDocumentoRepositoryInterface $documentoRepository,
        private ExpedienteRequerimientoCampoRepositoryInterface $campoRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
    ) {
    }

    public function listoParaPresentar(ExpedienteRequerimientoMercurio $requerimiento): bool
    {
        $documentos = $this->documentoRepository->findByRequerimientoId($requerimiento->id());
        $campos = $this->campoRepository->findByRequerimientoId($requerimiento->id());

        if ([] === $documentos && [] === $campos) {
            return false;
        }

        foreach ($documentos as $documento) {
            if (!$documento->estaCompletoParaPresentacion()) {
                return false;
            }
        }

        foreach ($campos as $campo) {
            if (!$campo->estaCompleto()) {
                return false;
            }
        }

        return true;
    }

    public function sincronizarEstadoTrasCambioCliente(ExpedienteRequerimientoMercurio $requerimiento): ExpedienteRequerimientoMercurio
    {
        if (DestinoRequerimientoMercurio::Cliente !== $requerimiento->destino()) {
            return $requerimiento;
        }
        if (EstadoRequerimientoMercurio::PendienteCliente !== $requerimiento->estado()) {
            return $requerimiento;
        }

        if (!$this->parteClienteCompletada($requerimiento)) {
            return $requerimiento;
        }

        $actualizado = $requerimiento->withEstado(EstadoRequerimientoMercurio::PendienteDespacho);
        $this->requerimientoRepository->save($actualizado);

        return $actualizado;
    }

    private function parteClienteCompletada(ExpedienteRequerimientoMercurio $requerimiento): bool
    {
        $documentos = $this->documentoRepository->findByRequerimientoId($requerimiento->id());
        foreach ($documentos as $documento) {
            if (ResponsableRequerimientoDocumento::Cliente !== $documento->responsable()) {
                continue;
            }
            if ($documento->obligatorio()
                && !in_array($documento->estado(), [
                    EstadoRequerimientoDocumento::Entregado,
                    EstadoRequerimientoDocumento::Validado,
                ], true)
            ) {
                return false;
            }
        }

        foreach ($this->campoRepository->findByRequerimientoId($requerimiento->id()) as $campo) {
            if (!$campo->estaCompleto()) {
                return false;
            }
        }

        return true;
    }
}
