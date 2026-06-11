<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\ContratacionAccesoPresenter;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\TramiteId;

final class ObtenerAccesoExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private InicializarContratacionUseCase $inicializarContratacion,
        private ContratacionAccesoPresenter $presenter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $token): array
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido o expirado.');
        }

        $tramiteNombre = '';
        if (null !== $expediente->tramiteId()) {
            $tramite = $this->tramiteRepository->findById(new TramiteId($expediente->tramiteId()));
            $tramiteNombre = $tramite?->nombre() ?? '';
        }

        if ($expediente->faseNegocio() === FaseNegocioExpediente::Contratacion) {
            ($this->inicializarContratacion)($expediente->id());
        }

        $vista = $this->presenter->present($expediente, $token);

        return [
            'expedienteNumero' => $expediente->numero(),
            'tramiteNombre' => $tramiteNombre,
            'faseNegocio' => $expediente->faseNegocio()->value,
            'faseNegocioLabel' => $expediente->faseNegocio()->label(),
            'estadoFase' => $expediente->estadoFase()->value,
            'estadoFaseLabel' => $expediente->estadoFase()->label(),
            'honorariosAcordados' => $expediente->honorariosAcordados(),
            'metodoPago' => $expediente->metodoPago()->value,
            'planPago' => $expediente->planPago()->value,
            'numCuotas' => $expediente->numCuotas(),
            ...$vista,
        ];
    }
}
