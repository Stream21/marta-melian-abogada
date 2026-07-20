<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Expediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;

final class ExpedienteAvisosAggregator
{
    public function __construct(
        private ContratacionRepositoryInterface $contratacionRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoRepository,
        private ContratacionPasoValidacionService $pasoValidacionService,
    ) {
    }

    /**
     * @param Expediente[] $expedientes
     *
     * @return array<string, array{contratacion: int, requerimientos: int, total: int}>
     */
    public function aggregate(array $expedientes): array
    {
        if ([] === $expedientes) {
            return [];
        }

        $ids = array_map(static fn (Expediente $expediente): string => $expediente->id()->value(), $expedientes);
        $pasosByExpediente = $this->contratacionRepository->findPasosByExpedienteIds($ids);
        $requerimientosByExpediente = $this->documentoRepository->countPendientesRevisionByExpedienteIds($ids);

        $result = [];
        foreach ($expedientes as $expediente) {
            $expedienteId = $expediente->id()->value();
            $pasos = $pasosByExpediente[$expedienteId] ?? [];
            $contratacion = $this->pasoValidacionService->countPasosPendientesRevision($expediente, $pasos);
            $requerimientos = $requerimientosByExpediente[$expedienteId] ?? 0;

            $result[$expedienteId] = [
                'contratacion' => $contratacion,
                'requerimientos' => $requerimientos,
                'total' => $contratacion + $requerimientos,
            ];
        }

        return $result;
    }
}
