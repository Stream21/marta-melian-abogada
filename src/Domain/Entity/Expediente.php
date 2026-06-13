<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;

final readonly class Expediente
{
    public function __construct(
        private ExpedienteId $id,
        private string $numero,
        private string $titulo,
        private EstadoExpediente $estado,
        private \DateTimeImmutable $fechaApertura,
        private string $clientName = '',
        private string $caseReference = '',
        private string $folderPath = '',
        private string $paymentStatus = 'pending',
        private ?string $clienteId = null,
        private ?string $tramiteId = null,
        private ?string $servicioId = null,
        private FaseNegocioExpediente $faseNegocio = FaseNegocioExpediente::Contratacion,
        private EstadoFaseExpediente $estadoFase = EstadoFaseExpediente::PendienteCliente,
        private float $honorariosAcordados = 0.0,
        private MetodoPagoExpediente $metodoPago = MetodoPagoExpediente::Manual,
        private PlanPagoExpediente $planPago = PlanPagoExpediente::Unico,
        private int $numCuotas = 1,
        private ?string $accessToken = null,
        private ?\DateTimeImmutable $fechaVencimientoFase = null,
        private ?\DateTimeImmutable $fechaUltimoCambioEstado = null,
        private ?\DateTimeImmutable $fechaFirmaContrato = null,
        /** @var list<array{numero: int, importe: float, fechaVencimiento: string, estado: string}>|null */
        private ?array $calendarioPagos = null,
    ) {
    }

    public function id(): ExpedienteId
    {
        return $this->id;
    }

    public function numero(): string
    {
        return $this->numero;
    }

    public function titulo(): string
    {
        return $this->titulo;
    }

    public function estado(): EstadoExpediente
    {
        return $this->estado;
    }

    public function fechaApertura(): \DateTimeImmutable
    {
        return $this->fechaApertura;
    }

    public function clientName(): string
    {
        return $this->clientName;
    }

    public function caseReference(): string
    {
        return $this->caseReference;
    }

    public function folderPath(): string
    {
        return $this->folderPath;
    }

    public function paymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function clienteId(): ?string
    {
        return $this->clienteId;
    }

    public function tramiteId(): ?string
    {
        return $this->tramiteId;
    }

    public function servicioId(): ?string
    {
        return $this->servicioId;
    }

    public function faseNegocio(): FaseNegocioExpediente
    {
        return $this->faseNegocio;
    }

    public function estadoFase(): EstadoFaseExpediente
    {
        return $this->estadoFase;
    }

    public function honorariosAcordados(): float
    {
        return $this->honorariosAcordados;
    }

    public function metodoPago(): MetodoPagoExpediente
    {
        return $this->metodoPago;
    }

    public function planPago(): PlanPagoExpediente
    {
        return $this->planPago;
    }

    public function numCuotas(): int
    {
        return $this->numCuotas;
    }

    public function accessToken(): ?string
    {
        return $this->accessToken;
    }

    public function fechaVencimientoFase(): ?\DateTimeImmutable
    {
        return $this->fechaVencimientoFase;
    }

    public function fechaUltimoCambioEstado(): ?\DateTimeImmutable
    {
        return $this->fechaUltimoCambioEstado;
    }

    public function fechaFirmaContrato(): ?\DateTimeImmutable
    {
        return $this->fechaFirmaContrato;
    }

    /**
     * @return list<array{numero: int, importe: float, fechaVencimiento: string, estado: string}>|null
     */
    public function calendarioPagos(): ?array
    {
        return $this->calendarioPagos;
    }

    public function withCondicionesPago(
        float $honorariosAcordados,
        MetodoPagoExpediente $metodoPago,
        PlanPagoExpediente $planPago,
        int $numCuotas,
    ): self {
        return $this->rebuild(
            honorariosAcordados: $honorariosAcordados,
            metodoPago: $metodoPago,
            planPago: $planPago,
            numCuotas: $numCuotas,
        );
    }

    /**
     * @param list<array{numero: int, importe: float, fechaVencimiento: string, estado: string}>|null $calendarioPagos
     */
    public function withCalendarioPagos(?array $calendarioPagos): self
    {
        return $this->rebuild(calendarioPagos: $calendarioPagos, calendarioPagosProvided: true);
    }

    public function withFechaFirmaContrato(?\DateTimeImmutable $fechaFirmaContrato): self
    {
        return $this->rebuild(fechaFirmaContrato: $fechaFirmaContrato);
    }

    public function withClienteId(?string $clienteId): self
    {
        return $this->rebuild(clienteId: $clienteId);
    }

    public function withTramiteId(?string $tramiteId): self
    {
        return $this->rebuild(tramiteId: $tramiteId);
    }

    public function withEstadoFase(EstadoFaseExpediente $estadoFase): self
    {
        return $this->rebuild(estadoFase: $estadoFase);
    }

    public function withFaseNegocio(FaseNegocioExpediente $faseNegocio, ?EstadoFaseExpediente $estadoFase = null): self
    {
        return $this->rebuild(faseNegocio: $faseNegocio, estadoFase: $estadoFase);
    }

    public function withPaymentStatus(string $paymentStatus): self
    {
        return $this->rebuild(paymentStatus: $paymentStatus);
    }

    public function withClientName(string $clientName): self
    {
        return $this->rebuild(clientName: $clientName);
    }

    public function withFechaVencimientoFase(?\DateTimeImmutable $fecha): self
    {
        return $this->rebuild(fechaVencimientoFase: $fecha);
    }

    public function touchEstadoCambio(): self
    {
        return $this->rebuild(fechaUltimoCambioEstado: new \DateTimeImmutable('now'));
    }

    private function rebuild(
        ?string $clienteId = null,
        ?string $tramiteId = null,
        ?string $servicioId = null,
        ?FaseNegocioExpediente $faseNegocio = null,
        ?EstadoFaseExpediente $estadoFase = null,
        ?float $honorariosAcordados = null,
        ?MetodoPagoExpediente $metodoPago = null,
        ?PlanPagoExpediente $planPago = null,
        ?int $numCuotas = null,
        ?string $accessToken = null,
        ?string $clientName = null,
        ?string $folderPath = null,
        ?string $numero = null,
        ?string $titulo = null,
        ?string $paymentStatus = null,
        ?\DateTimeImmutable $fechaVencimientoFase = null,
        ?\DateTimeImmutable $fechaUltimoCambioEstado = null,
        ?\DateTimeImmutable $fechaFirmaContrato = null,
        ?array $calendarioPagos = null,
        bool $calendarioPagosProvided = false,
    ): self {
        return new self(
            $this->id,
            $numero ?? $this->numero,
            $titulo ?? $this->titulo,
            $this->estado,
            $this->fechaApertura,
            $clientName ?? $this->clientName,
            $this->caseReference,
            $folderPath ?? $this->folderPath,
            $paymentStatus ?? $this->paymentStatus,
            $clienteId ?? $this->clienteId,
            $tramiteId ?? $this->tramiteId,
            $servicioId ?? $this->servicioId,
            $faseNegocio ?? $this->faseNegocio,
            $estadoFase ?? $this->estadoFase,
            $honorariosAcordados ?? $this->honorariosAcordados,
            $metodoPago ?? $this->metodoPago,
            $planPago ?? $this->planPago,
            $numCuotas ?? $this->numCuotas,
            $accessToken ?? $this->accessToken,
            $fechaVencimientoFase ?? $this->fechaVencimientoFase,
            $fechaUltimoCambioEstado ?? $this->fechaUltimoCambioEstado,
            $fechaFirmaContrato ?? $this->fechaFirmaContrato,
            $calendarioPagosProvided ? $calendarioPagos : $this->calendarioPagos,
        );
    }

    public static function crearAlta(
        ExpedienteId $id,
        string $numero,
        string $titulo,
        string $clientName,
        string $folderPath,
        string $clienteId,
        string $tramiteId,
        string $servicioId,
        float $honorariosAcordados,
        MetodoPagoExpediente $metodoPago,
        PlanPagoExpediente $planPago,
        int $numCuotas,
        string $accessToken,
        ?\DateTimeImmutable $fechaVencimientoFase = null,
    ): self {
        return new self(
            $id,
            $numero,
            $titulo,
            EstadoExpediente::Abierto,
            new \DateTimeImmutable('now'),
            $clientName,
            '',
            $folderPath,
            'pending',
            $clienteId,
            $tramiteId,
            $servicioId,
            FaseNegocioExpediente::Contratacion,
            EstadoFaseExpediente::PendienteCliente,
            $honorariosAcordados,
            $metodoPago,
            $planPago,
            $numCuotas,
            $accessToken,
            $fechaVencimientoFase,
            new \DateTimeImmutable('now'),
        );
    }
}
