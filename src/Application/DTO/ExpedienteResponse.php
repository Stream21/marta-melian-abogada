<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ExpedienteResponse
{
    /**
     * @param array{contratacion: int, requerimientos: int} $avisosDetalle
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
     * }|null $subfaseRequerimientos
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
        public int $avisosPendientes = 0,
        public array $avisosDetalle = ['contratacion' => 0, 'requerimientos' => 0],
        /** Subfase 1–3 dentro de contratación (identidad, firmas, pago). */
        public ?array $subfaseContratacion = null,
        /** Progreso documental en fase requerimientos. */
        public ?array $subfaseRequerimientos = null,
    ) {
    }
}
