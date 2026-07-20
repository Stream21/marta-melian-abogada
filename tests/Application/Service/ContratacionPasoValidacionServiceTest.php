<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Service\ContratacionPasoValidacionService;
use App\Domain\Entity\ContratacionPaso;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\ValueObject\ExpedienteId;
use PHPUnit\Framework\TestCase;

final class ContratacionPasoValidacionServiceTest extends TestCase
{
    private ContratacionPasoValidacionService $service;

    protected function setUp(): void
    {
        $this->service = new ContratacionPasoValidacionService();
    }

    public function testRequiereValidacionCuandoClienteHaCompletadoPaso(): void
    {
        $expediente = $this->crearExpediente();
        $paso = $this->crearPaso(PasoContratacionCliente::DatosCliente, EstadoPasoContratacion::RealizadoCliente);

        self::assertTrue($this->service->requiereValidacionAbogado($paso, $expediente, [$paso]));
    }

    public function testNoRequiereValidacionCuandoPasoEstaPendiente(): void
    {
        $expediente = $this->crearExpediente();
        $paso = $this->crearPaso(PasoContratacionCliente::DatosCliente, EstadoPasoContratacion::Pendiente);

        self::assertFalse($this->service->requiereValidacionAbogado($paso, $expediente, [$paso]));
    }

    public function testRequiereValidacionParaPagoManualCuandoFirmasValidadas(): void
    {
        $expediente = $this->crearExpediente(MetodoPagoExpediente::Manual);
        $firmas = $this->crearPaso(PasoContratacionCliente::Firmas, EstadoPasoContratacion::ValidadoAbogado);
        $pago = $this->crearPaso(PasoContratacionCliente::Pago, EstadoPasoContratacion::Pendiente);
        $pasos = [$firmas, $pago];

        self::assertTrue($this->service->requiereValidacionAbogado($pago, $expediente, $pasos));
    }

    public function testNoRequiereValidacionParaPagoManualSinFirmasValidadas(): void
    {
        $expediente = $this->crearExpediente(MetodoPagoExpediente::Manual);
        $firmas = $this->crearPaso(PasoContratacionCliente::Firmas, EstadoPasoContratacion::RealizadoCliente);
        $pago = $this->crearPaso(PasoContratacionCliente::Pago, EstadoPasoContratacion::Pendiente);
        $pasos = [$firmas, $pago];

        self::assertFalse($this->service->requiereValidacionAbogado($pago, $expediente, $pasos));
    }

    public function testCountPasosPendientesRevision(): void
    {
        $expediente = $this->crearExpediente(MetodoPagoExpediente::Manual);
        $datos = $this->crearPaso(PasoContratacionCliente::DatosCliente, EstadoPasoContratacion::ValidadoAbogado);
        $firmas = $this->crearPaso(PasoContratacionCliente::Firmas, EstadoPasoContratacion::ValidadoAbogado);
        $pago = $this->crearPaso(PasoContratacionCliente::Pago, EstadoPasoContratacion::Pendiente);
        $pasos = [$datos, $firmas, $pago];

        self::assertSame(1, $this->service->countPasosPendientesRevision($expediente, $pasos));
    }

    private function crearExpediente(MetodoPagoExpediente $metodoPago = MetodoPagoExpediente::Digital): Expediente
    {
        return Expediente::crearAlta(
            new ExpedienteId('exp-1'),
            'EXP-2026/0001',
            'Expediente test',
            'Cliente Test',
            '/tmp/exp',
            'cliente-1',
            'tramite-1',
            'servicio-1',
            1000.0,
            $metodoPago,
            PlanPagoExpediente::Unico,
            1,
            'token-1',
        );
    }

    private function crearPaso(PasoContratacionCliente $paso, EstadoPasoContratacion $estado): ContratacionPaso
    {
        return new ContratacionPaso(
            'paso-' . $paso->value,
            new ExpedienteId('exp-1'),
            $paso,
            $estado,
        );
    }
}
