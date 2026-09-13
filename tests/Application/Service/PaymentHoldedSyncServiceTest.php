<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\DTO\Holded\ClienteHoldedData;
use App\Application\DTO\Holded\ExpedienteInvoiceData;
use App\Application\Port\ContratacionRealtimePort;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Port\HoldedPort;
use App\Application\Service\CalendarioCobrosService;
use App\Application\Service\CalendarioPagoService;
use App\Application\Service\DocumentoFiscalValidator;
use App\Application\Service\IgicInvoiceCalculator;
use App\Application\Service\NotificarFalloSyncHoldedService;
use App\Application\Service\PaymentHoldedSyncService;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentHoldedEstado;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\PaymentId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class PaymentHoldedSyncServiceTest extends TestCase
{
    public function testPrimeraCuotaCreaFacturaYPay(): void
    {
        $fake = new FakeHoldedPort();
        $expedienteRepo = $this->createMock(ExpedienteRepositoryInterface::class);
        $expedienteRepo->expects(self::once())->method('save');

        $service = $this->buildService($fake, $expedienteRepo);

        $expediente = $this->expediente(1000.0, null, [
            ['numero' => 1, 'importe' => 500.0, 'fechaVencimiento' => '2026-06-01', 'estado' => 'pendiente'],
            ['numero' => 2, 'importe' => 500.0, 'fechaVencimiento' => '2026-07-01', 'estado' => 'pendiente'],
        ]);
        $payment = $this->payment($expediente->id(), '500.00', 1);

        $result = $service->sync($payment, $expediente);

        self::assertTrue($result['success']);
        self::assertSame(1, $fake->invoicesCreated);
        self::assertSame(1, $fake->paymentsRecorded);
        self::assertNotNull($result['expediente']->holdedInvoiceId());
        self::assertSame(PaymentHoldedEstado::Sincronizado, $result['payment']->holdedEstado());
    }

    public function testSegundaCuotaSoloPay(): void
    {
        $fake = new FakeHoldedPort();
        $expedienteRepo = $this->createMock(ExpedienteRepositoryInterface::class);
        $expedienteRepo->expects(self::never())->method('save');

        $service = $this->buildService($fake, $expedienteRepo);

        $expediente = $this->expediente(1000.0, 'inv-existente', [
            ['numero' => 1, 'importe' => 500.0, 'fechaVencimiento' => '2026-06-01', 'estado' => 'pagado'],
            ['numero' => 2, 'importe' => 500.0, 'fechaVencimiento' => '2026-07-01', 'estado' => 'pendiente'],
        ]);
        $payment = $this->payment($expediente->id(), '500.00', 2);

        $result = $service->sync($payment, $expediente);

        self::assertTrue($result['success']);
        self::assertSame(0, $fake->invoicesCreated);
        self::assertSame(1, $fake->paymentsRecorded);
        self::assertSame('inv-existente', $result['payment']->holdedInvoiceId());
    }

    public function testFalloHttpDejaPendienteSync(): void
    {
        $fake = new FakeHoldedPort();
        $fake->failOnCreate = true;
        $expedienteRepo = $this->createMock(ExpedienteRepositoryInterface::class);

        $contratacionRepo = $this->createMock(ContratacionRepositoryInterface::class);
        $contratacionRepo->method('findHitoById')->willReturn(null);
        $contratacionRepo->expects(self::once())->method('saveHito');

        $service = $this->buildService($fake, $expedienteRepo, $contratacionRepo);
        $expediente = $this->expediente(500.0, null, null);
        $payment = $this->payment($expediente->id(), '500.00', 1);

        $result = $service->sync($payment, $expediente);

        self::assertFalse($result['success']);
        self::assertSame(PaymentHoldedEstado::PendienteSync, $result['payment']->holdedEstado());
    }

    private function buildService(
        FakeHoldedPort $fake,
        ExpedienteRepositoryInterface $expedienteRepo,
        ?ContratacionRepositoryInterface $contratacionRepo = null,
    ): PaymentHoldedSyncService {
        $fileStorage = $this->createMock(ExpedienteFileStoragePort::class);
        $fileStorage->method('savePdf')->willReturn('storage/test/factura.pdf');

        $clienteRepo = $this->createMock(ClienteRepositoryInterface::class);
        $clienteRepo->method('findById')->willReturn(null);

        /** @var ContratacionRepositoryInterface&MockObject $contratacionRepo */
        $contratacionRepo ??= $this->createMock(ContratacionRepositoryInterface::class);
        $contratacionRepo->method('findHitoById')->willReturn(null);

        $realtime = $this->createMock(ContratacionRealtimePort::class);
        $notificar = new NotificarFalloSyncHoldedService($contratacionRepo, $realtime);

        return new PaymentHoldedSyncService(
            $fake,
            $fileStorage,
            $clienteRepo,
            $expedienteRepo,
            new IgicInvoiceCalculator(0.0, 'exento'),
            new DocumentoFiscalValidator(),
            new CalendarioCobrosService(new CalendarioPagoService()),
            $notificar,
            new NullLogger(),
        );
    }

    /**
     * @param list<array{numero: int, importe: float, fechaVencimiento: string, estado: string}>|null $calendario
     */
    private function expediente(float $honorarios, ?string $holdedInvoiceId, ?array $calendario): Expediente
    {
        $e = new Expediente(
            ExpedienteId::generate(),
            '2026-001',
            'Arraigo',
            \App\Domain\Entity\EstadoExpediente::Abierto,
            new \DateTimeImmutable('2026-01-01'),
            'Cliente Test',
            '',
            '',
            'pending',
            null,
            null,
            null,
            \App\Domain\Entity\FaseNegocioExpediente::Contratacion,
            \App\Domain\Entity\EstadoFaseExpediente::PendienteCliente,
            $honorarios,
            \App\Domain\Entity\MetodoPagoExpediente::Digital,
            PlanPagoExpediente::Fraccionado,
            2,
            null,
            null,
            null,
            null,
            $calendario,
            null,
            $holdedInvoiceId,
        );

        return $e;
    }

    private function payment(ExpedienteId $expedienteId, string $amount, int $cuota): Payment
    {
        $now = new \DateTimeImmutable('now');

        return new Payment(
            PaymentId::generate(),
            $expedienteId,
            PaymentStatus::Paid,
            PaymentType::Link,
            null,
            'cs_test',
            $amount,
            null,
            $now,
            $now,
            PaymentHoldedEstado::PendienteSync,
            null,
            null,
            $cuota,
        );
    }
}

final class FakeHoldedPort implements HoldedPort
{
    public int $invoicesCreated = 0;
    public int $paymentsRecorded = 0;
    public bool $failOnCreate = false;

    public function findOrCreateContact(ClienteHoldedData $clientData): string
    {
        return $clientData->existingHoldedContactId ?: 'contact-fake-1';
    }

    public function createInvoice(string $holdedContactId, ExpedienteInvoiceData $caseData): string
    {
        if ($this->failOnCreate) {
            throw new \RuntimeException('Holded 500');
        }
        ++$this->invoicesCreated;

        return 'inv-fake-' . $this->invoicesCreated;
    }

    public function recordPayment(
        string $holdedInvoiceId,
        float $amount,
        string $description,
        string $paymentMethod,
    ): void {
        ++$this->paymentsRecorded;
    }

    public function getInvoicePdf(string $holdedInvoiceId): string
    {
        return "%PDF-1.4\nfake\n%%EOF\n";
    }

    public function listContacts(): array
    {
        return [];
    }
}
