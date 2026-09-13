<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Holded\ClienteHoldedData;
use App\Application\DTO\Holded\ExpedienteInvoiceData;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Port\HoldedPort;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentHoldedEstado;
use App\Domain\Entity\PaymentType;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use Psr\Log\LoggerInterface;

/**
 * Orquesta factura única Holded por expediente + /pay por cada cuota
 * (régimen exento de IVA/IGIC en Las Palmas).
 */
final class PaymentHoldedSyncService
{
    public function __construct(
        private HoldedPort $holdedPort,
        private ExpedienteFileStoragePort $fileStorage,
        private ClienteRepositoryInterface $clienteRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private IgicInvoiceCalculator $igicCalculator,
        private DocumentoFiscalValidator $documentoValidator,
        private CalendarioCobrosService $calendarioCobrosService,
        private NotificarFalloSyncHoldedService $notificarFallo,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{payment: Payment, expediente: Expediente, success: bool, error?: string}
     */
    public function sync(Payment $payment, Expediente $expediente, bool $isRetry = false): array
    {
        if ($payment->status()->value !== 'paid') {
            return [
                'payment' => $payment,
                'expediente' => $expediente,
                'success' => false,
                'error' => 'El cobro debe estar marcado como pagado antes de sincronizar con Holded.',
            ];
        }

        if ($payment->holdedEstado() === PaymentHoldedEstado::Sincronizado
            && null !== $payment->holdedInvoiceId()
            && '' !== $payment->holdedInvoiceId()
        ) {
            return ['payment' => $payment, 'expediente' => $expediente, 'success' => true];
        }

        $fresh = $this->expedienteRepository->findById($expediente->id());
        if (null !== $fresh) {
            $expediente = $fresh;
        }

        $holdedInvoiceId = trim((string) ($expediente->holdedInvoiceId() ?? $payment->holdedInvoiceId() ?? ''));

        try {
            $cliente = $this->resolveCliente($expediente);
            $contactId = $this->ensureContact($cliente, $expediente);

            if ('' === $holdedInvoiceId) {
                $breakdown = $this->igicCalculator->fromTotalWithTax($expediente->honorariosAcordados());
                $nowCanary = new \DateTimeImmutable('now', new \DateTimeZone('Atlantic/Canary'));
                $holdedInvoiceId = $this->holdedPort->createInvoice(
                    $contactId,
                    new ExpedienteInvoiceData(
                        description: sprintf('Expediente %s — %s', $expediente->numero(), $expediente->titulo()),
                        totalWithTax: $breakdown['total'],
                        itemName: $expediente->titulo() !== '' ? $expediente->titulo() : 'Servicios legales',
                        subtotal: $breakdown['subtotal'],
                        taxKey: $breakdown['taxKey'],
                        dateUnix: $nowCanary->getTimestamp(),
                        taxes: $breakdown['taxes'],
                    ),
                );

                if ('' === $holdedInvoiceId) {
                    throw new \RuntimeException('Holded no devolvió un identificador de factura.');
                }

                $expediente = $expediente->withHoldedInvoiceId($holdedInvoiceId);
                $this->expedienteRepository->save($expediente);
            }

            $method = match ($payment->type()) {
                PaymentType::Manual => 'CASH',
                default => 'STRIPE',
            };
            $cuota = $payment->cuotaNumero();
            $desc = sprintf(
                'Cobro%s — expediente %s%s',
                null !== $cuota ? ' cuota ' . $cuota : '',
                $expediente->numero(),
                PaymentType::Manual === $payment->type() ? ' (efectivo/TPV despacho)' : ' (Stripe)',
            );

            $this->holdedPort->recordPayment(
                $holdedInvoiceId,
                (float) $payment->amount(),
                $desc,
                $method,
            );

            $pdfPath = $payment->pdfPath();
            try {
                $pdfContent = $this->holdedPort->getInvoicePdf($holdedInvoiceId);
                if (!$this->isValidPdf($pdfContent)) {
                    throw new \RuntimeException('Holded devolvió un archivo que no es un PDF válido.');
                }
                $filename = 'factura_' . $holdedInvoiceId . '.pdf';
                $pdfPath = $this->fileStorage->savePdf($payment->expedienteId(), $filename, $pdfContent);
            } catch (\Throwable $e) {
                $this->logger->warning('PaymentHoldedSync: PDF no descargado', [
                    'paymentId' => $payment->id()->value(),
                    'holdedInvoiceId' => $holdedInvoiceId,
                    'error' => $e->getMessage(),
                ]);
            }

            $now = new \DateTimeImmutable('now');
            $updated = $payment->withHoldedSync(
                PaymentHoldedEstado::Sincronizado,
                $holdedInvoiceId,
                $pdfPath,
                null,
                $now,
            );

            return [
                'payment' => $updated,
                'expediente' => $expediente,
                'success' => true,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('PaymentHoldedSync: fallo Holded', [
                'paymentId' => $payment->id()->value(),
                'expedienteId' => $payment->expedienteId()->value(),
                'error' => $e->getMessage(),
            ]);

            $estadoOnFailure = $isRetry
                ? PaymentHoldedEstado::Error
                : PaymentHoldedEstado::PendienteSync;

            $updated = $payment->withHoldedSync(
                $estadoOnFailure,
                '' !== $holdedInvoiceId ? $holdedInvoiceId : null,
                $payment->pdfPath(),
                $e->getMessage(),
            );

            if (!$isRetry) {
                $this->notificarFallo->notificar($expediente, $updated, $e->getMessage());
            }

            return [
                'payment' => $updated,
                'expediente' => $expediente,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function markPendingSync(Payment $payment): Payment
    {
        return $payment->withHoldedSync(PaymentHoldedEstado::PendienteSync, null, null, null);
    }

    /**
     * Recalcula pending|partial|paid a partir del calendario de cuotas.
     *
     * @param list<array{numero: int, importe: float, fechaVencimiento: string, estado: string}>|null $calendario
     */
    public function applyPaymentStatus(
        Expediente $expediente,
        ?array $calendario = null,
        ?float $importeCobradoExtra = null,
    ): Expediente {
        $cal = $calendario ?? $expediente->calendarioPagos();
        $status = $this->calendarioCobrosService->resolverPaymentStatus(
            $cal,
            $expediente->honorariosAcordados(),
            $importeCobradoExtra,
        );
        $updated = $expediente->withPaymentStatus($status);
        if (null !== $calendario) {
            $updated = $updated->withCalendarioPagos($calendario);
        }

        return $updated;
    }

    private function resolveCliente(Expediente $expediente): ?Cliente
    {
        $clienteId = $expediente->clienteId();
        if (null === $clienteId || '' === $clienteId) {
            return null;
        }

        return $this->clienteRepository->findById(new ClienteId($clienteId));
    }

    private function ensureContact(?Cliente $cliente, Expediente $expediente): string
    {
        if (null !== $cliente && $cliente->estaSincronizadoHolded()) {
            return (string) $cliente->holdedContactId();
        }

        $nombre = $cliente?->nombre() ?: $expediente->clientName();
        if ('' === trim($nombre)) {
            $nombre = 'Cliente ' . substr($expediente->id()->value(), 0, 8);
        }

        $email = trim((string) ($cliente?->email() ?? ''));
        if ('' === $email) {
            $email = 'contacto+' . $expediente->id()->value() . '@oportunidad.bufete.local';
        }

        $doc = trim((string) ($cliente?->numDocumento() ?? ''));
        $tipo = trim((string) ($cliente?->tipoDocumento() ?? ''));
        $country = $cliente?->countryCode() ?? 'ES';

        if ('' !== $doc) {
            $this->documentoValidator->assertValid($tipo !== '' ? $tipo : 'PASAPORTE', $doc, $country);
        } else {
            $doc = $expediente->caseReference() !== '' ? $expediente->caseReference() : $expediente->numero();
        }

        $contactId = $this->holdedPort->findOrCreateContact(new ClienteHoldedData(
            name: $nombre,
            email: $email,
            documentNumber: $doc,
            documentType: $tipo,
            address: $cliente?->domicilio() ?? '',
            city: $cliente?->ciudad() ?? '',
            postalCode: $cliente?->codigoPostal() ?? '',
            countryCode: $country,
            existingHoldedContactId: $cliente?->holdedContactId(),
            phone: $cliente?->telefono() ?? '',
        ));

        if (null !== $cliente) {
            $this->clienteRepository->save($cliente->withHoldedSincronizado($contactId));
        }

        return $contactId;
    }

    private function isValidPdf(string $content): bool
    {
        return str_starts_with($content, '%PDF-');
    }
}
