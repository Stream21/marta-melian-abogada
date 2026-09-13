<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\Holded\ClienteHoldedData;
use App\Application\Port\HoldedPort;
use App\Application\Service\DocumentoFiscalValidator;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use Psr\Log\LoggerInterface;

final class SincronizarClienteHoldedUseCase
{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository,
        private HoldedPort $holdedPort,
        private DocumentoFiscalValidator $documentoValidator,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{success: bool, holdedContactId?: string, error?: string}
     */
    public function __invoke(string $clienteId, bool $forzar = false): array
    {
        $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
        if (null === $cliente) {
            throw new \InvalidArgumentException('Cliente no encontrado.');
        }

        if ($cliente->estaSincronizadoHolded() && !$forzar) {
            return [
                'success' => true,
                'holdedContactId' => $cliente->holdedContactId(),
            ];
        }

        $nombre = trim($cliente->nombre());
        if ('' === $nombre) {
            $nombre = 'Cliente ' . substr($cliente->id()->value(), 0, 8);
        }

        $email = trim($cliente->email());
        if ('' === $email) {
            $email = 'contacto+' . $cliente->id()->value() . '@oportunidad.bufete.local';
        }

        try {
            $doc = trim($cliente->numDocumento());
            if ('' !== $doc) {
                $this->documentoValidator->assertValid(
                    $cliente->tipoDocumento() !== '' ? $cliente->tipoDocumento() : 'PASAPORTE',
                    $doc,
                    $cliente->countryCode(),
                );
            }

            $holdedId = $this->holdedPort->findOrCreateContact(new ClienteHoldedData(
                name: $nombre,
                email: $email,
                documentNumber: $doc !== '' ? $doc : $cliente->id()->value(),
                documentType: $cliente->tipoDocumento(),
                address: $cliente->domicilio(),
                city: $cliente->ciudad(),
                postalCode: $cliente->codigoPostal(),
                countryCode: $cliente->countryCode(),
                existingHoldedContactId: $forzar ? null : $cliente->holdedContactId(),
                phone: $cliente->telefono(),
            ));

            $this->clienteRepository->save($cliente->withHoldedSincronizado($holdedId));

            return ['success' => true, 'holdedContactId' => $holdedId];
        } catch (\Throwable $e) {
            $mensaje = $e->getMessage();
            $this->logger->error('Error sincronizando cliente con Holded: {message}', [
                'clienteId' => $clienteId,
                'message' => $mensaje,
            ]);
            $this->clienteRepository->save($cliente->withHoldedError($mensaje));

            return ['success' => false, 'error' => $mensaje];
        }
    }
}
