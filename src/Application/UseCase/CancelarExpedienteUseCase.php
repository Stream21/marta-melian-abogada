<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class CancelarExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
    ) {
    }

    public function __invoke(string $expedienteId, ?string $motivo = null): Expediente
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        try {
            $actualizado = $expediente->cancelar();
        } catch (\DomainException $e) {
            throw new \InvalidArgumentException($e->getMessage(), 0, $e);
        }

        $this->expedienteRepository->save($actualizado);

        $descripcion = 'Expediente cancelado.';
        $motivoLimpio = null !== $motivo ? trim($motivo) : '';
        if ('' !== $motivoLimpio) {
            $descripcion .= ' Motivo: ' . $motivoLimpio;
        }

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'expediente_cancelado',
            $descripcion,
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        return $actualizado;
    }
}
