<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;

final class RegistrarHitoClienteContratacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private InicializarContratacionUseCase $inicializarContratacion,
    ) {
    }

    public function __invoke(string $accessToken, string $pasoValue): void
    {
        $expediente = $this->expedienteRepository->findByAccessToken($accessToken);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('El expediente no está en fase de contratación.');
        }

        $pasoEnum = PasoContratacionCliente::tryFrom($pasoValue)
            ?? throw new \InvalidArgumentException('Paso no válido.');

        ($this->inicializarContratacion)($expediente->id());

        $paso = $this->contratacionRepository->findPaso($expediente->id(), $pasoEnum);
        if (null === $paso) {
            throw new \InvalidArgumentException('Paso no encontrado.');
        }

        if ($paso->estado() !== EstadoPasoContratacion::Pendiente) {
            return;
        }

        $this->contratacionRepository->savePaso($paso->marcarRealizadoCliente());

        $descripciones = [
            'documentacion' => 'El cliente ha subido la documentación requerida.',
            'datos_cliente' => 'El cliente ha completado sus datos personales.',
            'firmas' => 'El cliente ha firmado los documentos legales.',
            'pago' => 'El cliente ha registrado el pago inicial.',
        ];

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $expediente->id(),
            'paso_completado',
            $descripciones[$pasoEnum->value] ?? 'Paso completado por el cliente.',
            ActorHitoExpediente::Cliente,
            new \DateTimeImmutable('now'),
            $pasoEnum,
        ));
    }
}
