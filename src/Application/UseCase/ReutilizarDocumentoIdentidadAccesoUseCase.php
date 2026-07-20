<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;

final class ReutilizarDocumentoIdentidadAccesoUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    public function __invoke(string $token): void
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido o expirado.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('Este expediente ya no está en fase de contratación.');
        }

        $clienteId = $expediente->clienteId();
        if (null === $clienteId || '' === $clienteId) {
            throw new \InvalidArgumentException('Expediente sin cliente vinculado.');
        }

        $pasoDatos = $this->contratacionRepository->findPaso(
            $expediente->id(),
            PasoContratacionCliente::DatosCliente,
        );
        if (null === $pasoDatos || $pasoDatos->estado() !== EstadoPasoContratacion::Pendiente) {
            throw new \InvalidArgumentException('El paso de datos del cliente no está disponible en este momento.');
        }

        $pasoActivo = $this->resolverPasoActivoCliente($expediente->id());
        if (PasoContratacionCliente::DatosCliente !== $pasoActivo) {
            throw new \InvalidArgumentException('Debe completar los pasos anteriores antes de continuar.');
        }

        $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
        if (null === $cliente) {
            throw new \InvalidArgumentException('Cliente no encontrado.');
        }

        if (!$cliente->tieneDocumentoIdentidad()) {
            throw new \InvalidArgumentException('Debe escanear su documento de identidad para continuar.');
        }

        $this->contratacionRepository->savePaso($pasoDatos->marcarRealizadoCliente());

        $this->expedienteRepository->save(
            $expediente->withEstadoFase(EstadoFaseExpediente::PendienteFirma),
        );

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $expediente->id(),
            'datos_cliente_reutilizados',
            'El cliente ha reutilizado su documento de identidad registrado.',
            ActorHitoExpediente::Cliente,
            new \DateTimeImmutable('now'),
            PasoContratacionCliente::DatosCliente,
        ));

        $this->realtime->publishContratacionUpdate($expediente->id()->value(), [
            'type' => 'paso_completado',
            'paso' => PasoContratacionCliente::DatosCliente->value,
            'actor' => 'cliente',
        ]);
    }

    private function resolverPasoActivoCliente(\App\Domain\ValueObject\ExpedienteId $expedienteId): ?PasoContratacionCliente
    {
        $pasos = $this->contratacionRepository->findPasosByExpediente($expedienteId);
        $porPaso = [];
        foreach ($pasos as $paso) {
            $porPaso[$paso->paso()->value] = $paso;
        }

        foreach (PasoContratacionCliente::ordenados() as $ordenPaso) {
            $paso = $porPaso[$ordenPaso->value] ?? null;
            if (null === $paso) {
                continue;
            }

            if ($paso->estado() === EstadoPasoContratacion::ValidadoAbogado) {
                continue;
            }

            if ($paso->estado() === EstadoPasoContratacion::RealizadoCliente) {
                return null;
            }

            return $ordenPaso;
        }

        return null;
    }
}
