<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ExpedienteResponse
{
    public function __construct(
        public string $id,
        public string $numero,
        public string $titulo,
        public string $estado,
        public string $fechaApertura,
        public string $clientName = '',
        public string $caseReference = '',
        public string $folderPath = '',
        public string $paymentStatus = 'pending',
        public ?string $clienteId = null,
        public ?string $tramiteId = null,
        public ?string $servicioId = null,
        public string $faseNegocio = 'contratacion',
        public string $estadoFase = 'pendiente_cliente',
        public float $honorariosAcordados = 0.0,
        public string $metodoPago = 'manual',
        public string $planPago = 'unico',
        public int $numCuotas = 1,
        public ?string $accessUrl = null,
        public int $avisosPendientes = 0,
        /** @var array{contratacion: int, requerimientos: int} */
        public array $avisosDetalle = ['contratacion' => 0, 'requerimientos' => 0],
    ) {
    }
}
