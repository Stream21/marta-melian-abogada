<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ObtenerContratacionExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private InicializarContratacionUseCase $inicializarContratacion,
        private string $frontendBaseUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $expedienteId): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if ($expediente->faseNegocio() === FaseNegocioExpediente::Contratacion) {
            ($this->inicializarContratacion)($id);
        }

        $pasos = $this->contratacionRepository->findPasosByExpediente($id);
        usort($pasos, fn ($a, $b) => $a->paso()->orden() <=> $b->paso()->orden());

        $hitos = $this->contratacionRepository->findHitosByExpediente($id);

        $pasoActivo = $this->resolverPasoActivo($pasos);
        $todosValidados = $this->todosValidados($pasos);

        $accessUrl = null;
        if (null !== $expediente->accessToken()) {
            $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        }

        return [
            'expedienteId' => $expediente->id()->value(),
            'numero' => $expediente->numero(),
            'faseNegocio' => $expediente->faseNegocio()->value,
            'faseNegocioLabel' => $expediente->faseNegocio()->label(),
            'estadoFase' => $expediente->estadoFase()->value,
            'estadoFaseLabel' => $expediente->estadoFase()->label(),
            'metodoPago' => $expediente->metodoPago()->value,
            'metodoPagoLabel' => $expediente->metodoPago()->label(),
            'planPago' => $expediente->planPago()->value,
            'honorariosAcordados' => $expediente->honorariosAcordados(),
            'numCuotas' => $expediente->numCuotas(),
            'accessUrl' => $accessUrl,
            'pasoActivo' => $pasoActivo?->value,
            'contratacionCompletada' => $todosValidados,
            'pasos' => array_map(fn ($p) => [
                'paso' => $p->paso()->value,
                'label' => $p->paso()->label(),
                'descripcion' => $p->paso()->descripcion(),
                'orden' => $p->paso()->orden(),
                'estado' => $p->estado()->value,
                'estadoLabel' => $p->estado()->label(),
                'realizadoAt' => $p->realizadoAt()?->format(\DateTimeInterface::ATOM),
                'validadoAt' => $p->validadoAt()?->format(\DateTimeInterface::ATOM),
                'requiereValidacionAbogado' => $p->estado()->value === 'realizado_cliente',
            ], $pasos),
            'hitos' => array_map(fn ($h) => [
                'id' => $h->id(),
                'paso' => $h->paso()?->value,
                'tipo' => $h->tipo(),
                'descripcion' => $h->descripcion(),
                'actor' => $h->actor()->value,
                'createdAt' => $h->createdAt()->format(\DateTimeInterface::ATOM),
            ], $hitos),
        ];
    }

    /**
     * @param \App\Domain\Entity\ContratacionPaso[] $pasos
     */
    private function resolverPasoActivo(array $pasos): ?PasoContratacionCliente
    {
        foreach (PasoContratacionCliente::ordenados() as $ordenPaso) {
            foreach ($pasos as $paso) {
                if ($paso->paso() === $ordenPaso && $paso->estado()->value !== 'validado_abogado') {
                    return $ordenPaso;
                }
            }
        }

        return null;
    }

    /**
     * @param \App\Domain\Entity\ContratacionPaso[] $pasos
     */
    private function todosValidados(array $pasos): bool
    {
        if (count($pasos) < 4) {
            return false;
        }

        foreach ($pasos as $paso) {
            if ($paso->estado()->value !== 'validado_abogado') {
                return false;
            }
        }

        return true;
    }
}
