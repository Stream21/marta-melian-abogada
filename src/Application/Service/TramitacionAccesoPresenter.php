<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\DestinoRequerimientoMercurio;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\SubfaseTramitacion;
use App\Domain\Repository\ExpedientePresentacionTelematicaRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;

final class TramitacionAccesoPresenter
{
    private const INFOEXT_URL = 'https://sede.administracionespublicas.gob.es/pagina/index/directorio/infoext2';

    public function __construct(
        private ExpedientePresentacionTelematicaRepositoryInterface $presentacionRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private TramitacionSubfaseSyncService $subfaseSync,
        private RequerimientoMercurioPayloadBuilder $requerimientoPayload,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function present(Expediente $expediente): ?array
    {
        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            return null;
        }

        $expediente = $this->subfaseSync->sync($expediente);
        $subfase = $expediente->subfaseTramitacion();
        $presentacion = $this->presentacionRepository->findByExpediente($expediente->id());

        $requerimientosCliente = [];
        foreach ($this->requerimientoRepository->findByExpediente($expediente->id()) as $req) {
            $payload = $this->requerimientoPayload->buildRequerimiento($req, true);
            $hasClientDocs = [] !== ($payload['documentos'] ?? []);
            $hasCampos = [] !== ($payload['campos'] ?? []);
            if (
                DestinoRequerimientoMercurio::Cliente !== $req->destino()
                && !$hasClientDocs
                && !$hasCampos
            ) {
                continue;
            }
            $requerimientosCliente[] = $payload;
        }

        $estadoCliente = match ($subfase) {
            SubfaseTramitacion::PendienteTramitacion => 'preparacion',
            SubfaseTramitacion::Tramitado => 'en_seguimiento',
            SubfaseTramitacion::PendienteRequerimiento => [] !== array_filter(
                $requerimientosCliente,
                static fn (array $r) => ($r['estado'] ?? '') === 'pendiente_cliente',
            ) ? 'accion_requerida' : 'en_tramite_despacho',
            default => 'preparacion',
        };

        $estadoClienteLabel = match ($estadoCliente) {
            'preparacion' => 'En preparación por su abogado',
            'pendiente_tramitacion' => 'Pendiente de tramitación (Administración)',
            'en_seguimiento' => 'En seguimiento',
            'accion_requerida' => 'Acción requerida',
            'en_tramite_despacho' => 'En trámite con su abogado',
            default => 'En tramitación',
        };

        $numeroExpe = $presentacion?->numeroExpedienteExtranjeria();
        $instruccionesSeguimiento = null;
        if (null !== $numeroExpe && '' !== $numeroExpe) {
            $instruccionesSeguimiento = [
                'webUrl' => self::INFOEXT_URL,
                'sms' => sprintf('EXPE %s', $numeroExpe),
                'smsTelefono' => '651714610',
                'numeroExpedienteExtranjeria' => $numeroExpe,
                'texto' => sprintf(
                    'Consulte el estado en la sede electrónica o envíe un SMS gratuito con el texto «EXPE %s» al 651 714 610. Los datos tienen carácter informativo.',
                    $numeroExpe,
                ),
            ];
        }

        return [
            'subfase' => $subfase?->value,
            'subfaseLabel' => $subfase?->label(),
            'actorBandeja' => $subfase?->actorBandeja(),
            'estadoCliente' => $estadoCliente,
            'estadoClienteLabel' => $estadoClienteLabel,
            'fechaPresentacion' => $presentacion?->fechaPresentacion()->format('Y-m-d'),
            'numeroExpedienteExtranjeria' => $numeroExpe,
            'instruccionesSeguimiento' => $instruccionesSeguimiento,
            'requerimientosCliente' => $requerimientosCliente,
        ];
    }
}
