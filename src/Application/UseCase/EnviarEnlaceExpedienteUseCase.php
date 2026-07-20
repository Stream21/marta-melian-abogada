<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\NotificarAltaExpedienteService;
use App\Application\Service\TelefonoNormalizer;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteId;

final class EnviarEnlaceExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private NotificarAltaExpedienteService $notificarService,
        private ContratacionRepositoryInterface $contratacionRepository,
        private TelefonoNormalizer $telefonoNormalizer,
    ) {
    }

    /**
     * @param string[] $canales
     *
     * @return string[]
     */
    public function __invoke(string $expedienteId, array $canales): array
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (null === $expediente->accessToken() || '' === $expediente->accessToken()) {
            throw new \InvalidArgumentException('El expediente no tiene enlace de acceso.');
        }

        $clienteId = $expediente->clienteId();
        if (null === $clienteId || '' === $clienteId) {
            throw new \InvalidArgumentException('Expediente sin cliente vinculado.');
        }

        $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
        if (null === $cliente) {
            throw new \InvalidArgumentException('Cliente no encontrado.');
        }

        $canales = array_values(array_unique(array_filter(
            $canales,
            fn (string $c) => in_array($c, ['whatsapp', 'email'], true),
        )));

        if ([] === $canales) {
            throw new \InvalidArgumentException('Debe seleccionar al menos un canal (whatsapp o email).');
        }

        $telefono = $this->telefonoNormalizer->normalize($cliente->telefono());
        $email = trim($cliente->email());

        if (in_array('whatsapp', $canales, true)) {
            if (null === $telefono || !$this->telefonoNormalizer->isValid($telefono)) {
                throw new \InvalidArgumentException('El cliente debe tener un teléfono móvil válido para WhatsApp.');
            }
        }

        if (in_array('email', $canales, true) && '' === $email) {
            throw new \InvalidArgumentException('El cliente debe tener un email para notificar por correo.');
        }

        $tramiteNombre = 'Expediente';
        if (null !== $expediente->tramiteId()) {
            $tramite = $this->tramiteRepository->findById(new TramiteId($expediente->tramiteId()));
            $tramiteNombre = $tramite?->nombre() ?? $tramiteNombre;
        }

        $canalesEnviados = $this->notificarService->enviarEnlace($expediente, $cliente, $tramiteNombre, $canales);

        if ([] !== $canalesEnviados) {
            $this->contratacionRepository->saveHito(new ExpedienteHito(
                bin2hex(random_bytes(16)),
                new ExpedienteId($expedienteId),
                'notificacion_enlace_enviado',
                sprintf(
                    'Se ha enviado el enlace de acceso al cliente por %s.',
                    implode(', ', array_map(
                        fn (string $c) => match ($c) {
                            'whatsapp' => 'WhatsApp',
                            'email' => 'email',
                            default => $c,
                        },
                        array_filter($canalesEnviados, fn (string $c) => !str_ends_with($c, '_error')),
                    )),
                ),
                ActorHitoExpediente::Abogado,
                new \DateTimeImmutable('now'),
            ));
        }

        return $canalesEnviados;
    }
}
