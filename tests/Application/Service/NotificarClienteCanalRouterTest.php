<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Port\EmailPort;
use App\Application\Port\TwilioPort;
use App\Application\Service\NotificarClienteCanalRouter;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\EstadoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class NotificarClienteCanalRouterTest extends TestCase
{
    private TwilioPort&MockObject $twilio;
    private EmailPort&MockObject $email;
    private NotificarClienteCanalRouter $router;

    protected function setUp(): void
    {
        $this->twilio = $this->createMock(TwilioPort::class);
        $this->email = $this->createMock(EmailPort::class);
        $this->router = new NotificarClienteCanalRouter(
            $this->twilio,
            $this->email,
            new NullLogger(),
        );
    }

    public function testRespetaCanalesDelExpediente(): void
    {
        $this->twilio->method('isWhatsAppConfigured')->willReturn(true);
        $this->email->method('isConfigured')->willReturn(true);

        $this->twilio->expects(self::once())->method('sendWhatsAppMessage');
        $this->email->expects(self::never())->method('send');

        $canales = $this->router->enviar(
            $this->expediente(['whatsapp']),
            $this->cliente('+34600111222', 'ana@test.com'),
            'Asunto',
            'Mensaje',
        );

        self::assertSame(['whatsapp'], $canales);
    }

    public function testOmiteCanalSinDato(): void
    {
        $this->twilio->method('isWhatsAppConfigured')->willReturn(true);
        $this->email->method('isConfigured')->willReturn(true);

        $this->twilio->expects(self::never())->method('sendWhatsAppMessage');
        $this->email->expects(self::once())->method('send');

        $canales = $this->router->enviar(
            $this->expediente(['whatsapp', 'email']),
            $this->cliente('', 'ana@test.com'),
            'Asunto',
            'Mensaje',
        );

        self::assertSame(['email'], $canales);
    }

    public function testErrorDeTwilioNoImpideEmail(): void
    {
        $this->twilio->method('isWhatsAppConfigured')->willReturn(true);
        $this->twilio->method('sendWhatsAppMessage')->willThrowException(new \RuntimeException('down'));
        $this->email->method('isConfigured')->willReturn(true);

        $this->email->expects(self::once())->method('send');

        $canales = $this->router->enviar(
            $this->expediente(['whatsapp', 'email']),
            $this->cliente('+34600111222', 'ana@test.com'),
            'Asunto',
            'Mensaje',
        );

        self::assertSame(['email'], $canales);
    }

    public function testFallbackSiExpedienteSinPreferencia(): void
    {
        $this->twilio->method('isWhatsAppConfigured')->willReturn(true);
        $this->email->method('isConfigured')->willReturn(true);

        $this->twilio->expects(self::once())->method('sendWhatsAppMessage');
        $this->email->expects(self::once())->method('send');

        $canales = $this->router->enviar(
            $this->expediente([]),
            $this->cliente('+34600111222', 'ana@test.com'),
            'Asunto',
            'Mensaje',
        );

        self::assertSame(['whatsapp', 'email'], $canales);
    }

    public function testOverrideDeCanales(): void
    {
        $this->email->method('isConfigured')->willReturn(true);

        $this->twilio->expects(self::never())->method('sendWhatsAppMessage');
        $this->email->expects(self::once())->method('send');

        $canales = $this->router->enviar(
            $this->expediente(['whatsapp']),
            $this->cliente('+34600111222', 'ana@test.com'),
            'Asunto',
            'Mensaje',
            ['email'],
        );

        self::assertSame(['email'], $canales);
    }

    private function cliente(string $telefono, string $email): Cliente
    {
        return new Cliente(
            new ClienteId('cli-1'),
            'Ana Test',
            telefono: $telefono,
            email: $email,
        );
    }

    /** @param list<string> $canales */
    private function expediente(array $canales): Expediente
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
            $canales,
        );
    }
}
