<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ExpedienteResponseMapper;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;

final class ActualizarCanalesNotificacionExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private string $frontendBaseUrl,
    ) {
    }

    /**
     * @param list<string> $canales
     */
    public function __invoke(string $expedienteId, array $canales): \App\Application\DTO\ExpedienteResponse
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (!$expediente->estado()->isOperativo()) {
            throw new \InvalidArgumentException('Solo se pueden cambiar los canales en un expediente abierto.');
        }

        $canalesNormalizados = Expediente::normalizarCanales($canales);
        if ([] === $canalesNormalizados) {
            throw new \InvalidArgumentException('Debe seleccionar al menos un canal (WhatsApp o email).');
        }

        $cliente = null;
        if (null !== $expediente->clienteId() && '' !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
        }
        if (null === $cliente) {
            throw new \InvalidArgumentException('Expediente sin cliente vinculado.');
        }

        $telefono = trim($cliente->telefono());
        $email = trim($cliente->email());

        if (in_array('whatsapp', $canalesNormalizados, true) && '' === $telefono) {
            throw new \InvalidArgumentException('No se puede activar WhatsApp sin teléfono del cliente.');
        }
        if (in_array('email', $canalesNormalizados, true) && '' === $email) {
            throw new \InvalidArgumentException('No se puede activar email sin correo del cliente.');
        }

        $actualizado = $expediente->withCanalesNotificacion($canalesNormalizados);
        $this->expedienteRepository->save($actualizado);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'canales_notificacion_actualizados',
            sprintf(
                'Canales de comunicación con el cliente actualizados: %s.',
                implode(', ', array_map(
                    fn (string $c) => match ($c) {
                        'whatsapp' => 'WhatsApp',
                        'email' => 'email',
                        default => $c,
                    },
                    $canalesNormalizados,
                )),
            ),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        return ExpedienteResponseMapper::fromDomain($actualizado, $this->frontendBaseUrl, cliente: $cliente);
    }
}
