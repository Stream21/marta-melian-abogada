<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\NotificarContratacionClienteService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;

final class DevolverPasoContratacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private NotificarContratacionClienteService $notificarCliente,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    public function __invoke(string $expedienteId, string $pasoValue, string $nota, array $motivos = []): void
    {
        $nota = trim($nota);
        if (mb_strlen($nota) < 5) {
            throw new \InvalidArgumentException('Escriba una nota de al menos 5 caracteres para el cliente.');
        }

        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('El expediente no está en fase de contratación.');
        }

        $pasoEnum = PasoContratacionCliente::tryFrom($pasoValue)
            ?? throw new \InvalidArgumentException('Paso de contratación no válido.');

        $paso = $this->contratacionRepository->findPaso($id, $pasoEnum);
        if (null === $paso) {
            throw new \InvalidArgumentException('Paso no encontrado.');
        }

        $esNotificacionPagoManual = PasoContratacionCliente::Pago === $pasoEnum
            && MetodoPagoExpediente::Manual === $expediente->metodoPago()
            && EstadoPasoContratacion::Pendiente === $paso->estado();

        if (!$esNotificacionPagoManual && EstadoPasoContratacion::RealizadoCliente !== $paso->estado()) {
            throw new \InvalidArgumentException('Solo puede devolver pasos pendientes de su revisión.');
        }

        $pasoActualizado = $esNotificacionPagoManual
            ? $paso->notificarConNota($nota)
            : $paso->devolverConNota($nota, $motivos);

        $this->contratacionRepository->savePaso($pasoActualizado);

        $tipoHito = $esNotificacionPagoManual ? 'paso_notificado' : 'paso_devuelto';
        $descripcionHito = $esNotificacionPagoManual
            ? sprintf('El abogado ha notificado al cliente sobre el paso "%s": %s', $pasoEnum->label(), $nota)
            : sprintf('El abogado ha solicitado revisión del paso "%s": %s', $pasoEnum->label(), $nota);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            $tipoHito,
            $descripcionHito,
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
            $pasoEnum,
        ));

        if (!$esNotificacionPagoManual) {
            $this->expedienteRepository->save(
                $expediente
                    ->withEstadoFase(EstadoFaseExpediente::PendienteCliente)
                    ->touchEstadoCambio(),
            );
        }

        $clienteId = $expediente->clienteId();
        if (null !== $clienteId && '' !== $clienteId) {
            $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
            if (null !== $cliente) {
                $this->notificarCliente->notificarAccionRequerida($expediente, $cliente, $pasoEnum, $nota);
            }
        }

        $this->realtime->publishContratacionUpdate($id->value(), [
            'type' => $tipoHito,
            'paso' => $pasoEnum->value,
            'nota' => $nota,
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }
}
