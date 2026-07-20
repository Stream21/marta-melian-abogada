<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\RequerimientosCompletitudValidator;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteEscritoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ObtenerRequerimientosExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteEscritoRepositoryInterface $escritoRepository,
        private RequerimientosCompletitudValidator $completitudValidator,
        private string $frontendBaseUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $expedienteId): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de requerimientos.');
        }

        $entregas = [];
        foreach ($this->documentoEntregadoRepository->findByExpediente($id) as $entrega) {
            $docReqId = $entrega->expedienteDocumentoRequeridoId();
            if (null !== $docReqId) {
                $entregas[$docReqId->value()] = $entrega;
            }
        }

        $documentos = [];
        foreach ($this->documentoRequeridoRepository->findByExpediente($id) as $doc) {
            $entrega = $entregas[$doc->id()->value()] ?? null;
            $estado = $entrega?->estado()->value ?? EstadoDocumentoEntregado::Pendiente->value;
            $documentos[] = [
                'id' => $doc->id()->value(),
                'nombre' => $doc->nombre(),
                'descripcion' => $doc->descripcion(),
                'obligatorio' => $doc->obligatorio(),
                'tipo' => $doc->tipo()->value,
                'maxImagenes' => $doc->maxImagenes(),
                'orden' => $doc->orden(),
                'origen' => $doc->origen()->value,
                'origenLabel' => $doc->origen()->label(),
                'estado' => $estado,
                'estadoLabel' => $this->estadoLabel($estado),
                'entregadoAt' => $entrega?->entregadoAt()->format(\DateTimeInterface::ATOM),
                'notaRechazo' => $entrega?->notaRechazo(),
                'tieneArchivo' => null !== $entrega && '' !== $entrega->archivoPath(),
                'requiereRevision' => EstadoDocumentoEntregado::Entregado->value === $estado,
            ];
        }

        $progreso = $this->completitudValidator->resumen($id);
        $accessUrl = null !== $expediente->accessToken()
            ? rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken()
            : null;

        $escritos = array_map(fn ($e) => [
            'id' => $e->id(),
            'titulo' => $e->titulo(),
            'createdAt' => $e->createdAt()->format(\DateTimeInterface::ATOM),
        ], $this->escritoRepository->findByExpediente($id));

        return [
            'expedienteId' => $expediente->id()->value(),
            'numero' => $expediente->numero(),
            'faseNegocio' => $expediente->faseNegocio()->value,
            'estadoFase' => $expediente->estadoFase()->value,
            'estadoFaseLabel' => $expediente->estadoFase()->label(),
            'accessUrl' => $accessUrl,
            'documentos' => $documentos,
            'escritos' => $escritos,
            'progreso' => $progreso,
            'puedeAvanzarFase3' => $progreso['requerimientosListo']
                && EstadoFaseExpediente::RequerimientosListo === $expediente->estadoFase(),
        ];
    }

    private function estadoLabel(string $estado): string
    {
        return match ($estado) {
            EstadoDocumentoEntregado::Entregado->value => 'En revisión',
            EstadoDocumentoEntregado::Validado->value => 'Validado',
            EstadoDocumentoEntregado::Rechazado->value => 'Rechazado',
            default => 'Pendiente de entrega',
        };
    }
}
