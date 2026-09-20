<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Messenger;

use App\Application\Message\NotificarClienteMessage;
use App\Application\Service\NotificarClienteCanalRouter;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class NotificarClienteHandler
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private NotificarClienteCanalRouter $router,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(NotificarClienteMessage $message): void
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($message->expedienteId));
        if (null === $expediente) {
            $this->logger->warning('NotificarCliente: expediente no encontrado.', [
                'expedienteId' => $message->expedienteId,
                'tipo' => $message->tipo,
            ]);

            return;
        }

        $clienteId = $expediente->clienteId();
        if (null === $clienteId || '' === $clienteId) {
            $this->logger->warning('NotificarCliente: expediente sin cliente.', [
                'expediente' => $expediente->numero(),
                'tipo' => $message->tipo,
            ]);

            return;
        }

        $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
        if (null === $cliente) {
            $this->logger->warning('NotificarCliente: cliente no encontrado.', [
                'expediente' => $expediente->numero(),
                'tipo' => $message->tipo,
            ]);

            return;
        }

        $canales = $this->router->enviar(
            $expediente,
            $cliente,
            $message->asunto,
            $message->mensaje,
            $message->canalesOverride,
            $message->tipo,
        );

        if ([] === $canales || null === $message->hitoTipo) {
            return;
        }

        $descripcion = $message->hitoDescripcionPlantilla
            ?? 'Se ha notificado al cliente.';
        if (str_contains($descripcion, '%s')) {
            $descripcion = sprintf($descripcion, NotificarClienteCanalRouter::formatearCanales($canales));
        }

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $expediente->id(),
            $message->hitoTipo,
            $descripcion,
            ActorHitoExpediente::Sistema,
            new \DateTimeImmutable('now'),
        ));
    }
}
