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
            if (DestinoRequerimientoMercurio::Cliente !== $req->destino()) {
                continue;
            }
            $requerimientosCliente[] = [
                'id' => $req->id()->value(),
                'tipo' => $req->tipo()->value,
                'tipoLabel' => $req->tipo()->label(),
                'nombre' => $req->nombre(),
                'descripcion' => $req->descripcion(),
                'estado' => $req->estado()->value,
                'estadoLabel' => $req->estado()->label(),
                'tieneArchivo' => null !== $req->archivoPath() && '' !== $req->archivoPath(),
                'puedeSubir' => $req->estado()->value === 'pendiente_cliente',
            ];
        }

        $estadoCliente = match ($subfase) {
            SubfaseTramitacion::PreparacionPresentacion => 'preparacion',
            SubfaseTramitacion::PendienteRecepcion => 'pendiente_tramitacion',
            SubfaseTramitacion::EnSeguimiento, SubfaseTramitacion::ListoResolucion => 'en_seguimiento',
            SubfaseTramitacion::RequerimientoAbierto => [] !== array_filter(
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
