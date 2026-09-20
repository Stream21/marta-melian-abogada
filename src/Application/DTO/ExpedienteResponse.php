<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ExpedienteResponse
{
    /**
     * @param array{contratacion: int, documentacion: int, notificaciones?: int} $avisosDetalle
     * @param array{
     *     codigo: string,
     *     label: string,
     *     orden: int,
     *     total: int,
     *     estado: string,
     *     estadoLabel: string,
     *     items?: list<array{
     *         codigo: string,
     *         label: string,
     *         estado: string,
     *         estadoLabel: string,
     *         fecha: string|null
     *     }>
     * }|null $subfaseContratacion
     * @param array{
     *     validados: int,
     *     total: int,
     *     pendientes: int,
     *     enRevision: int,
     *     label: string,
     *     items: list<array{
     *         nombre: string,
     *         obligatorio: bool,
     *         estado: string,
     *         estadoLabel: string,
     *         fecha: string|null
     *     }>
     * }|null $subfaseDocumentacion
     * @param array{
     *     fechaPresentacion: string|null,
     *     items: list<array{
     *         nombre: string,
     *         estado: string,
     *         estadoLabel: string,
     *         fecha: string|null
     *     }>
     * }|null $subfaseTramitacionDetalle
     * @param array{
     *     pagadas: int,
     *     total: int,
     *     vencidas: int,
     *     label: string,
     *     cobrado: float,
     *     importeTotal: float,
     *     pendiente: float,
     *     items: list<array{
     *         nombre: string,
     *         importe: float,
     *         estado: string,
     *         estadoLabel: string,
     *         fecha: string|null
     *     }>
     * }|null $resumenCobros
     * @param array{contenido: string, createdAt: string, archivada: bool}|null $ultimaNota
     */
    public function __construct(
        public string $id,
        public string $numero,
        public string $titulo,
        public string $estado,
        public string $estadoLabel = '',
        public string $fechaApertura,
        public string $clientName = '',
        public string $caseReference = '',
        public string $folderPath = '',
        public string $paymentStatus = 'pending',
        public ?string $clienteId = null,
        /** True solo si el cliente ya está registrado (no alta provisional de contratación). */
        public bool $clienteFichaDisponible = false,
        public ?string $tramiteId = null,
        public ?string $servicioId = null,
        public string $faseNegocio = 'contratacion',
        public string $estadoFase = 'pendiente_cliente',
        public ?string $subfaseTramitacion = null,
        public ?string $subfaseTramitacionLabel = null,
        public ?string $actorBandejaTramitacion = null,
        public float $honorariosAcordados = 0.0,
        public string $metodoPago = 'manual',
        public string $planPago = 'unico',
        public int $numCuotas = 1,
        public ?string $accessUrl = null,
        /** Próxima fecha de vigencia de la fase/subfase actual. */
        public ?string $fechaVencimientoFase = null,
        public int $avisosPendientes = 0,
        public array $avisosDetalle = ['contratacion' => 0, 'documentacion' => 0, 'notificaciones' => 0],
        /** Subfase 1–3 dentro de contratación (identidad, firmas, pago). */
        public ?array $subfaseContratacion = null,
        /** Progreso documental en fase documentación. */
        public ?array $subfaseDocumentacion = null,
        /** Presentación/justificante para tooltip en fase tramitación. */
        public ?array $subfaseTramitacionDetalle = null,
        /** Progreso de cuotas/cobros para listado. */
        public ?array $resumenCobros = null,
        /** Notas activas (no archivadas) del expediente. */
        public int $notasActivas = 0,
        /** Última nota activa (más reciente) para preview en listado. */
        public ?array $ultimaNota = null,
        /** @var list<string> Preferencia de canales de aviso al cliente. */
        public array $canalesNotificacion = [],
        public bool $clienteTieneTelefono = false,
        public bool $clienteTieneEmail = false,
    ) {
    }
}
