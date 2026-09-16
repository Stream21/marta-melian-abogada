<?php

declare(strict_types=1);

namespace App\Tests\Application\UseCase;

use App\Application\Service\CalendarioCobrosService;
use App\Application\Service\CalendarioPagoService;
use App\Application\Service\CobrosResumenListadoService;
use App\Application\Service\ContratacionPasoValidacionService;
use App\Application\Service\ExpedienteAvisosAggregator;
use App\Application\Service\NotificacionDestinoResolver;
use App\Application\UseCase\ObtenerDashboardKpisUseCase;
use App\Application\UseCase\ObtenerNotificacionesRecientesUseCase;
use App\Domain\Entity\ContratacionPaso;
use App\Domain\Entity\EstadoExpediente;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\Gasto;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentHoldedEstado;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\GastoRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\GastoId;
use App\Domain\ValueObject\PaymentId;
use PHPUnit\Framework\TestCase;

final class ObtenerDashboardKpisUseCaseTest extends TestCase
{
    public function testCalculaBeneficioYVencimientosEnVentanaDeSieteDias(): void
    {
        $hoy = new \DateTimeImmutable('today');
        $expUrgente = $this->crearExpediente(
            'exp-1',
            '2026-0001',
            $hoy->modify('+3 days'),
        );
        $expLejano = $this->crearExpediente(
            'exp-2',
            '2026-0002',
            $hoy->modify('+20 days'),
        );

        $now = $hoy->setTime(10, 0);
        $payment = new Payment(
            PaymentId::generate(),
            new ExpedienteId('exp-1'),
            PaymentStatus::Paid,
            PaymentType::Manual,
            null,
            null,
            '1500.00',
            null,
            $now,
            $now,
            PaymentHoldedEstado::NoAplica,
        );

        $gasto = new Gasto(
            GastoId::generate(),
            'Alquiler oficina',
            '400.00',
            $hoy,
            'oficina',
            null,
            null,
            $now,
            $now,
        );

        $paymentRepo = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepo->method('findAll')->willReturn([$payment]);

        $gastoRepo = $this->createMock(GastoRepositoryInterface::class);
        $gastoRepo->method('findAll')->willReturn([$gasto]);

        $expedienteRepo = $this->createMock(ExpedienteRepositoryInterface::class);
        $expedienteRepo->method('findAll')->willReturn([$expUrgente, $expLejano]);

        $cobrosResumen = new CobrosResumenListadoService(
            new CalendarioCobrosService(new CalendarioPagoService()),
        );

        $pasoRevision = new ContratacionPaso(
            'paso-1',
            new ExpedienteId('exp-1'),
            PasoContratacionCliente::DatosCliente,
            EstadoPasoContratacion::RealizadoCliente,
        );

        $contratacionRepo = $this->createMock(ContratacionRepositoryInterface::class);
        $contratacionRepo->method('findPasosByExpedienteIds')->willReturn([
            'exp-1' => [$pasoRevision],
        ]);
        $contratacionRepo->method('countNotificacionesNoLeidasByExpedienteIds')->willReturn([]);
        $contratacionRepo->method('findHitosLeidosIds')->willReturn([]);
        $contratacionRepo->method('findRecentHitos')->willReturn([]);

        $documentoRepo = $this->createMock(ExpedienteDocumentoRepositoryInterface::class);
        $documentoRepo->method('countPendientesRevisionByExpedienteIds')->willReturn([
            'exp-1' => 2,
        ]);

        $avisos = new ExpedienteAvisosAggregator(
            $contratacionRepo,
            $documentoRepo,
            new ContratacionPasoValidacionService(),
        );

        $notificaciones = new ObtenerNotificacionesRecientesUseCase(
            $contratacionRepo,
            $this->createMock(ExpedienteRepositoryInterface::class),
            new NotificacionDestinoResolver(),
        );

        $useCase = new ObtenerDashboardKpisUseCase(
            $paymentRepo,
            $gastoRepo,
            $expedienteRepo,
            $cobrosResumen,
            $avisos,
            $notificaciones,
        );

        $result = ($useCase)();

        self::assertSame(1500.0, $result['financiero']['cobrosMes']);
        self::assertSame(400.0, $result['financiero']['gastosMes']);
        self::assertSame(1100.0, $result['financiero']['beneficioMes']);
        self::assertSame(2, $result['operativo']['expedientesActivos']);
        self::assertSame(2, $result['operativo']['documentacionPendienteRevision']);
        self::assertSame(1, $result['operativo']['contratacionPendienteRevision']);
        self::assertCount(1, $result['vencimientosProximos']);
        self::assertSame('exp-1', $result['vencimientosProximos'][0]['expedienteId']);
        self::assertSame(3, $result['vencimientosProximos'][0]['diasRestantes']);
    }

    private function crearExpediente(
        string $id,
        string $numero,
        \DateTimeImmutable $fechaVencimientoFase,
    ): Expediente {
        return new Expediente(
            new ExpedienteId($id),
            $numero,
            'Caso ' . $numero,
            EstadoExpediente::Abierto,
            $fechaVencimientoFase->modify('-30 days'),
            'Cliente Demo',
            'Trámite demo',
            '/tmp',
            'pending',
            null,
            null,
            null,
            FaseNegocioExpediente::Documentacion,
            EstadoFaseExpediente::PendienteCliente,
            0.0,
            MetodoPagoExpediente::Manual,
            PlanPagoExpediente::Unico,
            1,
            null,
            $fechaVencimientoFase,
        );
    }
}
