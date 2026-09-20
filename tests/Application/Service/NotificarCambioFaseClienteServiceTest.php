<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Message\NotificarClienteMessage;
use App\Application\Service\DespacharNotificacionClienteService;
use App\Application\Service\NotificarCambioFaseClienteService;
use App\Domain\Entity\EstadoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\OrigenDocumentoRequeridoExpediente;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\Entity\Cliente;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class NotificarCambioFaseClienteServiceTest extends TestCase
{
    private MessageBusInterface&MockObject $bus;
    private ClienteRepositoryInterface&MockObject $clientes;
    private ExpedienteDocumentoRequeridoRepositoryInterface&MockObject $docs;
    private NotificarCambioFaseClienteService $service;

    protected function setUp(): void
    {
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->clientes = $this->createMock(ClienteRepositoryInterface::class);
        $this->docs = $this->createMock(ExpedienteDocumentoRequeridoRepositoryInterface::class);

        $this->service = new NotificarCambioFaseClienteService(
            new DespacharNotificacionClienteService($this->bus),
            $this->clientes,
            $this->docs,
            new NullLogger(),
            'https://portal.example',
        );
    }

    public function testEncolaCambioADocumentacionConListaDeDocs(): void
    {
        $this->clientes->method('findById')->willReturn($this->cliente());
        $this->docs->method('findByExpediente')->willReturn([
            $this->doc('Pasaporte'),
            $this->doc('Empadronamiento'),
        ]);

        $this->bus->expects(self::once())->method('dispatch')->with(self::callback(
            function (NotificarClienteMessage $msg): bool {
                return 'cambio_fase_documentacion' === $msg->tipo
                    && str_contains($msg->mensaje, 'a) Pasaporte')
                    && str_contains($msg->mensaje, 'b) Empadronamiento')
                    && 'notificacion_cambio_fase' === $msg->hitoTipo;
            },
        ))->willReturnCallback(static fn (object $m) => new Envelope($m));

        $this->service->notificar($this->expediente(), FaseNegocioExpediente::Documentacion);
    }

    public function testNoNotificaPasoAResolucion(): void
    {
        $this->bus->expects(self::never())->method('dispatch');
        $this->service->notificar($this->expediente(), FaseNegocioExpediente::Resolucion);
    }

    public function testNoHaceNadaSinClienteNiEnContratacion(): void
    {
        $this->bus->expects(self::never())->method('dispatch');
        $this->service->notificar($this->expediente(clienteId: null), FaseNegocioExpediente::Documentacion);
        $this->service->notificar($this->expediente(), FaseNegocioExpediente::Contratacion);
    }

    private function cliente(): Cliente
    {
        return new Cliente(new ClienteId('cli-1'), 'Ana Test', telefono: '+34600111222', email: 'ana@test.com');
    }

    private function doc(string $nombre): ExpedienteDocumentoRequerido
    {
        return new ExpedienteDocumentoRequerido(
            new ExpedienteDocumentoRequeridoId(bin2hex(random_bytes(8))),
            new ExpedienteId('exp-1'),
            $nombre,
            '',
            true,
            TipoDocumentoRequerido::Individual,
            1,
            0,
            OrigenDocumentoRequeridoExpediente::Manual,
        );
    }

    private function expediente(?string $clienteId = 'cli-1'): Expediente
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
            $clienteId,
            null,
            null,
            FaseNegocioExpediente::Documentacion,
            EstadoFaseExpediente::DocumentacionEnProgreso,
            0.0,
            MetodoPagoExpediente::Manual,
            PlanPagoExpediente::Unico,
            1,
            'token-abc',
            null,
            null,
            null,
            null,
            null,
            null,
            ['whatsapp', 'email'],
        );
    }
}
