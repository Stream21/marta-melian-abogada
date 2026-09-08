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
                $tramiteDocId = $entregado->documentoRequeridoId();
                if (null === $tramiteDocId) {
                    continue;
                }
                $entregadosPorDoc[$tramiteDocId->value()] = true;
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

    /**
     * Subfase actual de contratación para listados (incluye «esperando abogado»).
     *
     * @return array{
     *     codigo: string,
     *     label: string,
     *     orden: int,
     *     total: int,
     *     estado: string,
     *     estadoLabel: string,
     *     items: list<array{
     *         codigo: string,
     *         label: string,
     *         estado: string,
     *         estadoLabel: string,
     *         fecha: string|null
     *     }>
     * }|null
     */
    public function subfaseContratacionParaListado(ExpedienteId $expedienteId): ?array
    {
        $pasos = $this->contratacionRepository->findPasosByExpediente($expedienteId);
        $porPaso = [];
        foreach ($pasos as $paso) {
            $porPaso[$paso->paso()->value] = $paso;
        }

        $items = [];
        foreach (PasoContratacionCliente::ordenados() as $ordenPaso) {
            $paso = $porPaso[$ordenPaso->value] ?? null;
            $estado = $paso?->estado() ?? EstadoPasoContratacion::Pendiente;
            $fecha = $paso?->validadoAt() ?? $paso?->realizadoAt();
            $items[] = [
                'codigo' => $ordenPaso->value,
                'label' => $ordenPaso->label(),
                'estado' => $estado->value,
                'estadoLabel' => $estado->label(),
                'fecha' => $fecha?->format(\DateTimeInterface::ATOM),
            ];
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
                return [
                    'codigo' => $ordenPaso->value,
                    'label' => $ordenPaso->label(),
                    'orden' => $ordenPaso->orden(),
                    'total' => PasoContratacionCliente::TOTAL,
                    'estado' => 'esperando_abogado',
                    'estadoLabel' => 'Esperando abogado',
                    'items' => $items,
                ];
            }

            return [
                'codigo' => $ordenPaso->value,
                'label' => $ordenPaso->label(),
                'orden' => $ordenPaso->orden(),
                'total' => PasoContratacionCliente::TOTAL,
                'estado' => 'pendiente_cliente',
                'estadoLabel' => 'Pendiente cliente',
                'items' => $items,
            ];
        }

        if ([] === $items) {
            return null;
        }

        // Todos validados: mostrar última subfase como completada.
        $ultimo = PasoContratacionCliente::ordenados();
        $ultimoPaso = $ultimo[array_key_last($ultimo)];

        return [
            'codigo' => $ultimoPaso->value,
            'label' => $ultimoPaso->label(),
            'orden' => $ultimoPaso->orden(),
            'total' => PasoContratacionCliente::TOTAL,
            'estado' => 'completado',
            'estadoLabel' => 'Completado',
            'items' => $items,
        ];
    }
}
