<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\FaseDocumentoTramite;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteFirmaRepositoryInterface;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteId;

final class ContratacionCompletitudValidator
{
    public function __construct(
        private TramiteDocumentoRequeridoRepositoryInterface $documentoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteFirmaRepositoryInterface $firmaRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
    ) {
    }

    public function documentacionCompleta(ExpedienteId $expedienteId, ?string $tramiteId): bool
    {
        if (null === $tramiteId || '' === $tramiteId) {
            return true;
        }

        $requeridos = $this->documentoRepository->findByTramiteId(new TramiteId($tramiteId));
        $entregados = $this->documentoEntregadoRepository->findByExpediente($expedienteId);
        $entregadosPorDoc = [];
        foreach ($entregados as $entregado) {
            if ($entregado->estado() !== EstadoDocumentoEntregado::Pendiente) {
                $entregadosPorDoc[$entregado->documentoRequeridoId()->value()] = true;
            }
        }

        foreach ($requeridos as $doc) {
            if ($doc->fase() !== FaseDocumentoTramite::DocumentacionBasica || !$doc->obligatorio()) {
                continue;
            }
            if (!isset($entregadosPorDoc[$doc->id()->value()])) {
                return false;
            }
        }

        return true;
    }

    public function firmasCompletas(ExpedienteId $expedienteId): bool
    {
        $firmas = $this->firmaRepository->findByExpediente($expedienteId);
        $firmados = [];
        foreach ($firmas as $firma) {
            $firmados[$firma->tipoEscrito()->value] = true;
        }

        foreach ([TipoEscrito::HojaEncargo, TipoEscrito::Designacion, TipoEscrito::Rgpd] as $tipo) {
            if (!isset($firmados[$tipo->value])) {
                return false;
            }
        }

        return true;
    }

    public function pasoActivoCliente(ExpedienteId $expedienteId): ?PasoContratacionCliente
    {
        $pasos = $this->contratacionRepository->findPasosByExpediente($expedienteId);
        $porPaso = [];
        foreach ($pasos as $paso) {
            $porPaso[$paso->paso()->value] = $paso;
        }

        foreach (PasoContratacionCliente::ordenados() as $ordenPaso) {
            $paso = $porPaso[$ordenPaso->value] ?? null;
            if (null === $paso) {
                continue;
            }

            if ($paso->estado() === EstadoPasoContratacion::ValidadoAbogado) {
                continue;
            }

            if ($paso->estado() === EstadoPasoContratacion::RealizadoCliente) {
                return null;
            }

            return $ordenPaso;
        }

        return null;
    }
}
