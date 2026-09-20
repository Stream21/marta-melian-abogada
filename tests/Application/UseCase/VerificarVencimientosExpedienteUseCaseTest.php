<?php

declare(strict_types=1);

namespace App\Tests\Application\UseCase;

use App\Application\Message\NotificarClienteMessage;
use App\Application\Service\DespacharNotificacionClienteService;
use App\Application\UseCase\VerificarVencimientosExpedienteUseCase;
use App\Domain\Entity\EstadoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\NotificacionVencimientoEnviadaRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class VerificarVencimientosExpedienteUseCaseTest extends TestCase
{
    private ExpedienteRepositoryInterface&MockObject $expedientes;
    private NotificacionVencimientoEnviadaRepositoryInterface&MockObject $enviadas;
    private MessageBusInterface&MockObject $bus;
    private VerificarVencimientosExpedienteUseCase $useCase;

    protected function setUp(): void
    {
        $this->expedientes = $this->createMock(ExpedienteRepositoryInterface::class);
        $this->enviadas = $this->createMock(NotificacionVencimientoEnviadaRepositoryInterface::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->useCase = new VerificarVencimientosExpedienteUseCase(
            $this->expedientes,
            $this->enviadas,
            new DespacharNotificacionClienteService($this->bus),
            'https://portal.example',
        );
    }

    public function testEncolaSoloEnDias7_3_0(): void
    {
        $hoy = new \DateTimeImmutable('2026-03-10');
        $this->expedientes->method('findAll')->willReturn([
            $this->expediente('exp-7', new \DateTimeImmutable('2026-03-17')),
            $this->expediente('exp-5', new \DateTimeImmutable('2026-03-15')),
            $this->expediente('exp-3', new \DateTimeImmutable('2026-03-13')),
            $this->expediente('exp-0', new \DateTimeImmutable('2026-03-10')),
            $this->expediente('exp-1', new \DateTimeImmutable('2026-03-11')),
        ]);
        $this->enviadas->method('exists')->willReturn(false);

        $dispatched = 0;
        $this->bus->method('dispatch')->willReturnCallback(function (object $m) use (&$dispatched) {
            self::assertInstanceOf(NotificarClienteMessage::class, $m);
            ++$dispatched;

            return new Envelope($m);
        });
        $this->enviadas->expects(self::exactly(3))->method('save');

        $count = ($this->useCase)($hoy);
        self::assertSame(3, $count);
        self::assertSame(3, $dispatched);
    }

    public function testNoDuplicaSiYaEnviado(): void
    {
        $hoy = new \DateTimeImmutable('2026-03-10');
        $this->expedientes->method('findAll')->willReturn([
            $this->expediente('exp-0', new \DateTimeImmutable('2026-03-10')),
        ]);
        $this->enviadas->method('exists')->willReturn(true);
        $this->bus->expects(self::never())->method('dispatch');
        $this->enviadas->expects(self::never())->method('save');

        self::assertSame(0, ($this->useCase)($hoy));
    }

    public function testIgnoraFaseResolucion(): void
    {
        $hoy = new \DateTimeImmutable('2026-03-10');
        $this->expedientes->method('findAll')->willReturn([
            new Expediente(
                new ExpedienteId('exp-r'),
                '2026-0002',
                'Res',
                EstadoExpediente::Abierto,
                new \DateTimeImmutable('2026-01-01'),
                'Ana',
                '',
                '',
                'pending',
                'cli-1',
                null,
                null,
                FaseNegocioExpediente::Resolucion,
                EstadoFaseExpediente::PendienteCliente,
                0.0,
                MetodoPagoExpediente::Manual,
                PlanPagoExpediente::Unico,
                1,
                'tok',
                new \DateTimeImmutable('2026-03-10'),
                null,
                null,
                null,
                null,
                null,
                ['email'],
            ),
        ]);
        $this->bus->expects(self::never())->method('dispatch');

        self::assertSame(0, ($this->useCase)($hoy));
    }

    private function expediente(string $id, \DateTimeImmutable $vencimiento): Expediente
    {
        return new Expediente(
            new ExpedienteId($id),
            '2026-' . $id,
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
            FaseNegocioExpediente::Documentacion,
            EstadoFaseExpediente::DocumentacionEnProgreso,
            0.0,
            MetodoPagoExpediente::Manual,
            PlanPagoExpediente::Unico,
            1,
            'token',
            $vencimiento,
            null,
            null,
            null,
            null,
            null,
            ['email'],
        );
    }
}
