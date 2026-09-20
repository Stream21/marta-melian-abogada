<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\EstadoRequerimientoDocumento;
use App\Domain\Entity\EstadoRequerimientoMercurio;
use App\Domain\Entity\ExpedienteRequerimientoMercurio;
use App\Domain\Repository\ExpedienteRequerimientoCampoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoDocumentoRepositoryInterface;

final class RequerimientoMercurioPayloadBuilder
{
    public function __construct(
        private ExpedienteRequerimientoDocumentoRepositoryInterface $documentoRepository,
        private ExpedienteRequerimientoCampoRepositoryInterface $campoRepository,
        private RequerimientoMercurioCompletitudService $completitud,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildRequerimiento(ExpedienteRequerimientoMercurio $req, bool $portalCliente = false): array
    {
        $documentos = [];
        foreach ($this->documentoRepository->findByRequerimientoId($req->id()) as $doc) {
            if ($portalCliente && 'cliente' !== $doc->responsable()->value) {
                continue;
            }
            $documentos[] = [
                'id' => $doc->id()->value(),
                'nombre' => $doc->nombre(),
                'descripcion' => $doc->descripcion(),
                'responsable' => $doc->responsable()->value,
                'responsableLabel' => $doc->responsable()->label(),
                'obligatorio' => $doc->obligatorio(),
                'estado' => $doc->estado()->value,
                'estadoLabel' => $doc->estado()->label(),
                'tieneArchivo' => null !== $doc->archivoPath() && '' !== $doc->archivoPath(),
                'notaRechazo' => $doc->notaRechazo(),
                'orden' => $doc->orden(),
                'maxArchivos' => $doc->maxArchivos(),
            ];
        }

        $campos = [];
        foreach ($this->campoRepository->findByRequerimientoId($req->id()) as $campo) {
            $campos[] = [
                'id' => $campo->id()->value(),
                'clave' => $campo->clave(),
                'etiqueta' => $campo->etiqueta(),
                'tipo' => $campo->tipo()->value,
                'tipoLabel' => $campo->tipo()->label(),
                'opciones' => $campo->opcionesJson(),
                'obligatorio' => $campo->obligatorio(),
                'orden' => $campo->orden(),
                'valor' => $portalCliente ? $campo->valor() : $campo->valor(),
            ];
        }

        $presentado = !$req->estado()->estaAbierto();
        $fechaPresentacion = $presentado
            ? $req->updatedAt()->format('Y-m-d')
            : null;

        $payload = [
            'id' => $req->id()->value(),
            'tipo' => $req->tipo()->value,
            'tipoLabel' => $req->tipo()->label(),
            'destino' => $req->destino()->value,
            'destinoLabel' => $req->destino()->label(),
            'nombre' => $req->nombre(),
            'descripcion' => $req->descripcion(),
            'formularioNombre' => $req->formularioNombre(),
            'formularioCometido' => $req->formularioCometido(),
            'estado' => $req->estado()->value,
            'estadoLabel' => $req->estado()->label(),
            'tieneArchivo' => null !== $req->archivoPath() && '' !== $req->archivoPath(),
            'archivoNombre' => $req->archivoNombre(),
            'tieneOficio' => $req->tieneOficio(),
            'oficioNombre' => $req->oficioNombre(),
            'tieneJustificante' => null !== $req->justificantePresentacionPath()
                && '' !== $req->justificantePresentacionPath(),
            'fechaPresentacion' => $fechaPresentacion,
            'documentos' => $documentos,
            'campos' => $campos,
            'listoParaPresentar' => $this->completitud->listoParaPresentar($req),
            'createdAt' => $req->createdAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $req->updatedAt()->format(\DateTimeInterface::ATOM),
        ];

        if ($portalCliente) {
            unset($payload['destino'], $payload['destinoLabel']);
            $payload['puedeSubir'] = $this->puedeSubirEnPortalCliente(
                $req,
                $documentos,
                $campos,
            );
        }

        return $payload;
    }

    /**
     * @param list<array<string, mixed>> $documentosCliente
     * @param list<array<string, mixed>> $campos
     */
    private function puedeSubirEnPortalCliente(
        ExpedienteRequerimientoMercurio $req,
        array $documentosCliente,
        array $campos,
    ): bool {
        if (!$req->estado()->estaAbierto()) {
            return false;
        }

        $docsPendientes = false;
        foreach ($documentosCliente as $doc) {
            $estado = (string) ($doc['estado'] ?? '');
            if (
                EstadoRequerimientoDocumento::Pendiente->value === $estado
                || EstadoRequerimientoDocumento::Rechazado->value === $estado
            ) {
                $docsPendientes = true;
                break;
            }
        }

        $camposPendientes = false;
        foreach ($campos as $campo) {
            $valor = $campo['valor'] ?? null;
            if (null === $valor || '' === trim((string) $valor)) {
                $camposPendientes = true;
                break;
            }
        }

        $legacyPendienteCliente = EstadoRequerimientoMercurio::PendienteCliente === $req->estado()
            && [] === $documentosCliente
            && [] === $campos;

        return $docsPendientes || $camposPendientes || $legacyPendienteCliente;
    }
}
