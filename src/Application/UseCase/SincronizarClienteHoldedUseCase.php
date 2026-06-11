<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\HoldedContactPort;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use Psr\Log\LoggerInterface;

final class SincronizarClienteHoldedUseCase
{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository,
        private HoldedContactPort $holdedContact,
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
            $holdedId = $this->holdedContact->createContact(
                $nombre,
                $email,
                $cliente->numDocumento(),
            );

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
