<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\NotificarResolucionClienteService;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\DespachoConfigRepositoryInterface;
use App\Domain\Repository\ExpedienteRecordatorioFuturoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;

final class EnviarRecordatoriosFuturosUseCase
{
    public function __construct(
        private ExpedienteRecordatorioFuturoRepositoryInterface $recordatorioRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private DespachoConfigRepositoryInterface $despachoConfigRepository,
        private NotificarResolucionClienteService $notificar,
    ) {
    }

    public function __invoke(?\DateTimeImmutable $hoy = null): int
    {
        $hoy ??= new \DateTimeImmutable('today');
        $finDia = $hoy->setTime(23, 59, 59);
        $enviados = 0;

        $config = $this->despachoConfigRepository->find();
        $emailDespacho = $config?->email() ?? '';

        foreach ($this->recordatorioRepository->findPendientesHasta($finDia) as $recordatorio) {
            $expediente = $this->expedienteRepository->findById($recordatorio->expedienteId());
            if (null === $expediente) {
                continue;
            }

            $clienteNombre = $expediente->clientName();
            if (null !== $expediente->clienteId() && '' !== $expediente->clienteId()) {
                $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
                if (null !== $cliente) {
                    $clienteNombre = $cliente->nombre();
                    $this->notificar->notificarRecordatorioFuturoCliente(
                        $expediente,
                        $cliente,
                        $recordatorio->motivo(),
                        $recordatorio->fecha(),
                    );
                }
            }

            $this->notificar->notificarRecordatorioFuturoDespacho(
                $emailDespacho,
                $expediente,
                $recordatorio->motivo(),
                $recordatorio->fecha(),
                $clienteNombre,
            );

            $this->recordatorioRepository->save($recordatorio->marcarNotificado());
            ++$enviados;
        }

        return $enviados;
    }
}
