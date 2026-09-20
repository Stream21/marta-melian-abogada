<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Symfony\Messenger;

use App\Application\Message\NotificarClienteMessage;
use App\Application\Port\EmailPort;
use App\Application\Port\TwilioPort;
use App\Application\Service\NotificarClienteCanalRouter;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\EstadoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Infrastructure\Symfony\Messenger\NotificarClienteHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class NotificarClienteHandlerTest extends TestCase
{
    private ExpedienteRepositoryInterface&MockObject $expedientes;
    private ClienteRepositoryInterface&MockObject $clientes;
    private ContratacionRepositoryInterface&MockObject $contratacion;
    private TwilioPort&MockObject $twilio;
    private EmailPort&MockObject $email;
    private NotificarClienteHandler $handler;

    protected function setUp(): void
    {
        $this->expedientes = $this->createMock(ExpedienteRepositoryInterface::class);
        $this->clientes = $this->createMock(ClienteRepositoryInterface::class);
        $this->contratacion = $this->createMock(ContratacionRepositoryInterface::class);
        $this->twilio = $this->createMock(TwilioPort::class);
        $this->email = $this->createMock(EmailPort::class);

        $this->handler = new NotificarClienteHandler(
            $this->expedientes,
            $this->clientes,
            $this->contratacion,
            new NotificarClienteCanalRouter($this->twilio, $this->email, new NullLogger()),
            new NullLogger(),
        );
    }

    public function testEnviaYRegistraHito(): void
    {
        $this->expedientes->method('findById')->willReturn($this->expediente());
        $this->clientes->method('findById')->willReturn(
            new Cliente(new ClienteId('cli-1'), 'Ana', telefono: '+34600111222', email: 'ana@test.com'),
        );
        $this->twilio->method('isWhatsAppConfigured')->willReturn(true);
        $this->email->method('isConfigured')->willReturn(true);

        $this->twilio->expects(self::once())->method('sendWhatsAppMessage');
        $this->email->expects(self::once())->method('send');
        $this->contratacion->expects(self::once())->method('saveHito')->with(self::callback(
            static fn (ExpedienteHito $h): bool => 'notificacion_alta_expediente' === $h->tipo()
                && ActorHitoExpediente::Sistema === $h->actor()
                && str_contains($h->descripcion(), 'WhatsApp')
                && str_contains($h->descripcion(), 'email'),
        ));

        ($this->handler)(new NotificarClienteMessage(
            'exp-1',
            'alta',
            'Asunto',
            'Mensaje',
            'notificacion_alta_expediente',
            'Se ha notificado al cliente el alta del expediente por %s.',
        ));
    }

    private function expediente(): Expediente
    {
        return new Expediente(
            new ExpedienteId('exp-1'),
            '2026-0001',
            'Caso',
            EstadoExpediente::Abierto,
            new \DateTimeImmutable('2026-01-01'),
            'Ana',
            '',
            '',
            'pending',
            'cli-1',
            null,
            null,
            FaseNegocioExpediente::Contratacion,
            EstadoFaseExpediente::PendienteCliente,
            0.0,
            MetodoPagoExpediente::Manual,
            PlanPagoExpediente::Unico,
            1,
            'token',
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
