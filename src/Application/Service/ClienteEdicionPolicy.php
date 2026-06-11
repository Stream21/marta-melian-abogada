<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;

final class ClienteEdicionPolicy
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
    ) {
    }

    public function puedeEditar(ClienteId $clienteId): bool
    {
        return [] === $this->expedientesConProcesoAbierto($clienteId);
    }

    /**
     * @return list<array{numero: string, titulo: string}>
     */
    public function expedientesConProcesoAbierto(ClienteId $clienteId): array
    {
        $abiertos = [];
        foreach ($this->expedienteRepository->findByClienteId($clienteId) as $expediente) {
            if ($expediente->faseNegocio() === FaseNegocioExpediente::Contratacion) {
                $abiertos[] = [
                    'numero' => $expediente->numero(),
                    'titulo' => $expediente->titulo(),
                ];
            }
        }

        return $abiertos;
    }

    public function motivoBloqueo(ClienteId $clienteId): ?string
    {
        $abiertos = $this->expedientesConProcesoAbierto($clienteId);
        if ([] === $abiertos) {
            return null;
        }

        $numeros = implode(', ', array_column($abiertos, 'numero'));

        return sprintf(
            'Los datos no se pueden modificar mientras el expediente %s esté en fase de contratación. Podrá editarlos cuando el proceso se haya cerrado.',
            $numeros,
        );
    }
}
