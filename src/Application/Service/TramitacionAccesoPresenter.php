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
        $presentada = null !== $presentacion;
        $numeroExpe = $presentacion?->numeroExpedienteExtranjeria();
        $tieneNumero = null !== $numeroExpe && '' !== $numeroExpe;

        $todosRequerimientos = $this->requerimientoRepository->findByExpediente($expediente->id());
        $requerimientosCliente = [];
        $reqsClientePendientes = 0;
        $reqsClientePresentados = 0;
        $reqsDespachoAbiertos = 0;

        foreach ($todosRequerimientos as $req) {
            $payload = $this->requerimientoPayload->buildRequerimiento($req, true);
            $hasClientDocs = [] !== ($payload['documentos'] ?? []);
            $hasCampos = [] !== ($payload['campos'] ?? []);
            $visibleCliente = DestinoRequerimientoMercurio::Cliente === $req->destino()
                || $hasClientDocs
                || $hasCampos;

            if ($visibleCliente) {
                $requerimientosCliente[] = $payload;
                if ($req->estado()->estaAbierto()) {
                    if (($payload['estado'] ?? '') === 'pendiente_cliente') {
                        ++$reqsClientePendientes;
                    }
                } else {
                    ++$reqsClientePresentados;
                }
            } elseif ($req->estado()->estaAbierto()) {
                ++$reqsDespachoAbiertos;
            }
        }

        $estadoCliente = $this->resolverEstadoCliente(
            $presentada,
            $tieneNumero,
            $reqsClientePendientes,
            $reqsDespachoAbiertos,
            $subfase,
        );

        $estadoClienteLabel = match ($estadoCliente) {
            'preparacion' => 'En preparación por su abogado',
            'presentada' => 'Solicitud presentada',
            'en_seguimiento' => 'En seguimiento ante la Administración',
            'accion_requerida' => 'Acción requerida por su parte',
            'en_tramite_despacho' => 'Su abogado gestiona un requerimiento',
            default => 'En tramitación',
        };

        $mensajeEstado = match ($estadoCliente) {
            'preparacion' => 'Su abogado está preparando la presentación telemática de su solicitud. Le avisaremos por correo cuando quede registrada ante la Administración.',
            'presentada' => 'Su solicitud ya está presentada. Cuando la Administración asigne el número de expediente de extranjería, podrá consultarlo aquí y le avisaremos por correo.',
            'en_seguimiento' => 'Su solicitud está en trámite ante la Administración. Puede consultar el estado con el número de seguimiento.',
            'accion_requerida' => 'La Administración o su abogado necesitan documentación o datos adicionales. Complételos abajo para continuar.',
            'en_tramite_despacho' => 'Hay un requerimiento en curso que está gestionando su abogado. No necesita hacer nada ahora; le avisaremos si se requiere su intervención.',
            default => 'Su expediente está en fase de tramitación.',
        };

        $instruccionesSeguimiento = null;
        if ($tieneNumero) {
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
            'mensajeEstado' => $mensajeEstado,
            'fechaPresentacion' => $presentacion?->fechaPresentacion()->format('Y-m-d'),
            'presentacionRegistrada' => $presentada,
            'numeroExpedienteExtranjeria' => $numeroExpe,
            'instruccionesSeguimiento' => $instruccionesSeguimiento,
            'timeline' => $this->buildTimeline(
                $presentada,
                $tieneNumero,
                $reqsClientePendientes,
                $reqsClientePresentados,
                $reqsDespachoAbiertos,
                $presentacion?->fechaPresentacion()->format('Y-m-d'),
            ),
            'requerimientosCliente' => $requerimientosCliente,
        ];
    }

    private function resolverEstadoCliente(
        bool $presentada,
        bool $tieneNumero,
        int $reqsClientePendientes,
        int $reqsDespachoAbiertos,
        ?SubfaseTramitacion $subfase,
    ): string {
        if ($reqsClientePendientes > 0) {
            return 'accion_requerida';
        }
        if ($reqsDespachoAbiertos > 0 || SubfaseTramitacion::PendienteRequerimiento === $subfase) {
            return 'en_tramite_despacho';
        }
        if (!$presentada) {
            return 'preparacion';
        }
        if (!$tieneNumero) {
            return 'presentada';
        }

        return 'en_seguimiento';
    }

    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     descripcion: string,
     *     estado: string,
     *     fecha: string|null
     * }>
     */
    private function buildTimeline(
        bool $presentada,
        bool $tieneNumero,
        int $reqsClientePendientes,
        int $reqsClientePresentados,
        int $reqsDespachoAbiertos,
        ?string $fechaPresentacion,
    ): array {
        $tieneRequerimientos = $reqsClientePendientes > 0
            || $reqsClientePresentados > 0
            || $reqsDespachoAbiertos > 0;

        $preparacionEstado = $presentada ? 'completado' : 'activo';
        $presentacionEstado = !$presentada
            ? 'pendiente'
            : ($tieneNumero || $tieneRequerimientos ? 'completado' : 'activo');
        $seguimientoEstado = !$presentada
            ? 'pendiente'
            : ($tieneNumero
                ? (($reqsClientePendientes > 0 || $reqsDespachoAbiertos > 0) ? 'completado' : 'activo')
                : 'pendiente');

        $timeline = [
            [
                'id' => 'preparacion',
                'label' => 'Preparación',
                'descripcion' => 'Su abogado prepara y revisa la documentación para presentar la solicitud.',
                'estado' => $preparacionEstado,
                'fecha' => null,
            ],
            [
                'id' => 'presentacion',
                'label' => 'Presentación',
                'descripcion' => $presentada
                    ? 'Solicitud presentada ante la Administración. Recibió aviso por correo.'
                    : 'Cuando se presente, le avisaremos por correo con la confirmación.',
                'estado' => $presentacionEstado,
                'fecha' => $fechaPresentacion,
            ],
            [
                'id' => 'seguimiento',
                'label' => 'Nº de seguimiento',
                'descripcion' => $tieneNumero
                    ? 'Ya puede consultar el estado en la sede electrónica y por SMS.'
                    : 'Pendiente de que la Administración asigne el número de expediente de extranjería.',
                'estado' => $seguimientoEstado,
                'fecha' => null,
            ],
        ];

        if ($tieneRequerimientos) {
            if ($reqsClientePendientes > 0) {
                $reqEstado = 'activo';
                $reqDesc = 'Hay documentación o datos pendientes por su parte.';
            } elseif ($reqsDespachoAbiertos > 0) {
                $reqEstado = 'activo';
                $reqDesc = 'Su abogado está gestionando un requerimiento de la Administración.';
            } else {
                $reqEstado = 'completado';
                $reqDesc = 'Los requerimientos adicionales ya están presentados.';
            }

            $timeline[] = [
                'id' => 'requerimiento',
                'label' => 'Requerimiento',
                'descripcion' => $reqDesc,
                'estado' => $reqEstado,
                'fecha' => null,
            ];
        }

        $timeline[] = [
            'id' => 'resolucion',
            'label' => 'Resolución',
            'descripcion' => 'Cuando termine la tramitación, su expediente pasará a la fase de resolución.',
            'estado' => 'pendiente',
            'fecha' => null,
        ];

        return $timeline;
    }
}
