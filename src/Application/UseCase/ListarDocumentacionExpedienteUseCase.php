<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\FaseDocumentoTramite;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteFirmaRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteId;

final class ListarDocumentacionExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private TramiteDocumentoRequeridoRepositoryInterface $documentoRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $expedienteDocumentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteFirmaRepositoryInterface $firmaRepository,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(string $expedienteId): array
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $items = [];

        if (null !== $expediente->clienteId() && '' !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente && $cliente->tieneDocumentoIdentidad()) {
                $tipoDoc = $cliente->documentoIdentidadTipo()?->label() ?? 'Documento de identidad';
                $escaneadoAt = $cliente->documentoIdentidadEscaneadoAt()?->format(\DateTimeInterface::ATOM);

                $items[] = $this->itemIdentidadCliente(
                    $expedienteId,
                    'identidad-anverso',
                    'Documento de identidad — Anverso',
                    sprintf('Escaneo del anverso (%s) aportado en contratación.', $tipoDoc),
                    $escaneadoAt,
                );

                if (null !== $cliente->documentoIdentidadReversoPath() && '' !== $cliente->documentoIdentidadReversoPath()) {
                    $items[] = $this->itemIdentidadCliente(
                        $expedienteId,
                        'identidad-reverso',
                        'Documento de identidad — Reverso',
                        sprintf('Escaneo del reverso (%s) aportado en contratación.', $tipoDoc),
                        $escaneadoAt,
                    );
                }
            }
        }

        $entregasTramite = [];
        $entregasExpediente = [];
        foreach ($this->documentoEntregadoRepository->findByExpediente($expediente->id()) as $doc) {
            if (null !== $doc->documentoRequeridoId()) {
                $entregasTramite[$doc->documentoRequeridoId()->value()] = $doc;
            }
            $expDocId = $doc->expedienteDocumentoRequeridoId();
            if (null !== $expDocId) {
                $entregasExpediente[$expDocId->value()] = $doc;
            }
        }

        if (null !== $expediente->tramiteId()) {
            foreach ($this->documentoRepository->findByTramiteId(new TramiteId($expediente->tramiteId())) as $doc) {
                if (FaseDocumentoTramite::DocumentosCliente === $doc->fase()) {
                    continue;
                }

                $entregado = $entregasTramite[$doc->id()->value()] ?? null;
                $faseNegocio = $this->faseNegocioDesdeDocumento($doc->fase());

                $items[] = [
                    'id' => $doc->id()->value(),
                    'nombre' => $doc->nombre(),
                    'descripcion' => $doc->descripcion(),
                    'tipo' => 'documento',
                    'fase' => $doc->fase()->value,
                    'faseLabel' => $doc->fase()->label(),
                    'faseNegocio' => $faseNegocio->value,
                    'faseNegocioLabel' => $faseNegocio->label(),
                    'origen' => 'requisito_tramite',
                    'origenLabel' => 'Requisito del trámite',
                    'obligatorio' => $doc->obligatorio(),
                    'estado' => $entregado?->estado()->value ?? 'pendiente',
                    'entregadoAt' => $entregado?->entregadoAt()->format(\DateTimeInterface::ATOM),
                    'descargaUrl' => null !== $entregado
                        ? sprintf('/api/expedientes/%s/documentacion/%s/archivo', $expedienteId, $doc->id()->value())
                        : null,
                    'mediaTipo' => 'pdf',
                ];
            }
        }

        foreach ($this->expedienteDocumentoRequeridoRepository->findByExpediente($expediente->id()) as $doc) {
            $entregado = $entregasExpediente[$doc->id()->value()] ?? null;

            $items[] = [
                'id' => $doc->id()->value(),
                'nombre' => $doc->nombre(),
                'descripcion' => $doc->descripcion(),
                'tipo' => 'documento',
                'fase' => FaseDocumentoTramite::DocumentosCliente->value,
                'faseLabel' => FaseDocumentoTramite::DocumentosCliente->label(),
                'faseNegocio' => FaseNegocioExpediente::Requerimientos->value,
                'faseNegocioLabel' => FaseNegocioExpediente::Requerimientos->label(),
                'origen' => 'requisito_expediente_' . $doc->origen()->value,
                'origenLabel' => $doc->origen()->label(),
                'obligatorio' => $doc->obligatorio(),
                'estado' => $entregado?->estado()->value ?? EstadoDocumentoEntregado::Pendiente->value,
                'entregadoAt' => $entregado?->entregadoAt()->format(\DateTimeInterface::ATOM),
                'descargaUrl' => null !== $entregado && '' !== $entregado->archivoPath()
                    ? sprintf('/api/expedientes/%s/documentacion/%s/archivo', $expedienteId, $doc->id()->value())
                    : null,
                'mediaTipo' => 'pdf',
            ];
        }

        foreach ($this->firmaRepository->findByExpediente($expediente->id()) as $firma) {
            $items[] = [
                'id' => 'firma-' . $firma->tipoEscrito()->value,
                'nombre' => $firma->tipoEscrito()->label() . ' (firmado)',
                'descripcion' => 'Documento legal firmado electrónicamente en contratación.',
                'tipo' => 'documento',
                'fase' => FaseDocumentoTramite::DocumentacionBasica->value,
                'faseLabel' => FaseDocumentoTramite::DocumentacionBasica->label(),
                'faseNegocio' => FaseNegocioExpediente::Contratacion->value,
                'faseNegocioLabel' => FaseNegocioExpediente::Contratacion->label(),
                'origen' => 'documento_firmado',
                'origenLabel' => 'Firma electrónica',
                'obligatorio' => true,
                'estado' => 'firmado',
                'entregadoAt' => $firma->firmadoAt()->format(\DateTimeInterface::ATOM),
                'descargaUrl' => sprintf(
                    '/api/expedientes/%s/contratacion/firmas/%s/pdf',
                    $expedienteId,
                    $firma->tipoEscrito()->value,
                ),
                'mediaTipo' => 'pdf',
            ];
        }

        usort($items, function (array $a, array $b): int {
            $fechaA = $a['entregadoAt'] ?? '';
            $fechaB = $b['entregadoAt'] ?? '';

            return strcmp($fechaB, $fechaA);
        });

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function itemIdentidadCliente(
        string $expedienteId,
        string $id,
        string $nombre,
        string $descripcion,
        ?string $entregadoAt,
    ): array {
        $lado = str_ends_with($id, 'reverso') ? 'reverso' : 'anverso';

        return [
            'id' => $id,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'tipo' => 'documento',
            'fase' => FaseDocumentoTramite::DocumentacionBasica->value,
            'faseLabel' => FaseDocumentoTramite::DocumentacionBasica->label(),
            'faseNegocio' => FaseNegocioExpediente::Contratacion->value,
            'faseNegocioLabel' => FaseNegocioExpediente::Contratacion->label(),
            'origen' => 'identidad_cliente',
            'origenLabel' => 'Identidad del cliente',
            'obligatorio' => true,
            'estado' => 'entregado',
            'entregadoAt' => $entregadoAt,
            'descargaUrl' => sprintf('/api/expedientes/%s/documentacion/identidad/%s', $expedienteId, $lado),
            'mediaTipo' => 'imagen',
        ];
    }

    private function faseNegocioDesdeDocumento(FaseDocumentoTramite $fase): FaseNegocioExpediente
    {
        return match ($fase) {
            FaseDocumentoTramite::DocumentacionBasica => FaseNegocioExpediente::Contratacion,
            FaseDocumentoTramite::DocumentosCliente => FaseNegocioExpediente::Requerimientos,
            FaseDocumentoTramite::GestionAbogado => FaseNegocioExpediente::Tramitacion,
            FaseDocumentoTramite::Resolucion => FaseNegocioExpediente::Resolucion,
        };
    }
}
