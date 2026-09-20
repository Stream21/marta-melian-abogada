<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Message\NotificarClienteMessage;
use App\Application\Service\DespacharNotificacionClienteService;
use App\Application\Service\NotificarContratacionClienteService;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\EstadoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class NotificarContratacionClienteServiceTest extends TestCase
{
    private MessageBusInterface&MockObject $bus;
    private NotificarContratacionClienteService $service;

    protected function setUp(): void
    {
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->service = new NotificarContratacionClienteService(
            new DespacharNotificacionClienteService($this->bus),
            'https://portal.example',
        );
    }

    public function testNotificaFirmasTrasValidarDatos(): void
    {
        $this->bus->expects(self::once())->method('dispatch')->with(self::callback(
            function (NotificarClienteMessage $msg): bool {
                return 'contratacion_paso_disponible_firmas' === $msg->tipo
                    && str_contains($msg->mensaje, 'firmar los documentos')
                    && str_contains($msg->mensaje, 'https://portal.example/acceso/tok-abc')
                    && 'notificacion_contratacion_paso' === $msg->hitoTipo;
            },
        ))->willReturnCallback(static fn (object $m) => new Envelope($m));

        $this->service->notificarPasoDisponible(
            $this->expediente(),
            $this->cliente(),
            PasoContratacionCliente::Firmas,
        );
    }

    public function testNotificaPagoTrasValidarFirmas(): void
    {
        $this->bus->expects(self::once())->method('dispatch')->with(self::callback(
            function (NotificarClienteMessage $msg): bool {
                return 'contratacion_paso_disponible_pago' === $msg->tipo
                    && str_contains($msg->mensaje, 'continuar con el pago')
                    && 'notificacion_contratacion_paso' === $msg->hitoTipo;
            },
        ))->willReturnCallback(static fn (object $m) => new Envelope($m));

        $this->service->notificarPasoDisponible(
            $this->expediente(),
            $this->cliente(),
            PasoContratacionCliente::Pago,
        );
    }

    public function testNoNotificaPasoDatosCliente(): void
    {
        $this->bus->expects(self::never())->method('dispatch');

        $this->service->notificarPasoDisponible(
            $this->expediente(),
            $this->cliente(),
            PasoContratacionCliente::DatosCliente,
        );
    }

    private function expediente(): Expediente
    {
        return new Expediente(
            new ExpedienteId('exp-1'),
            '2026-0001',
            'Caso demo',
            EstadoExpediente::Abierto,
            new \DateTimeImmutable('2026-01-01'),
            'Ana Test',
            '',
            '',
            'pending',
            'cli-1',
            null,
            null,
            FaseNegocioExpediente::Contratacion,
            EstadoFaseExpediente::PendienteFirma,
            0.0,
            MetodoPagoExpediente::Manual,
            PlanPagoExpediente::Unico,
            1,
            'tok-abc',
            null,
            null,
            null,
            null,
            null,
            null,
            ['whatsapp', 'email'],
        );
    }

    private function cliente(): Cliente
    {
        return new Cliente(new ClienteId('cli-1'), 'Ana Test', telefono: '+34600111222', email: 'ana@test.com');
    }
}
