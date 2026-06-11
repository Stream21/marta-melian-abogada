<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\ExpedienteRepositoryInterface;

final class GenerarDocumentoFirmaAccesoUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private GenerarEscritoPdfUseCase $generarEscritoPdf,
    ) {
    }

    public function __invoke(string $token, string $tipo): string
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido o expirado.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('Este expediente ya no está en fase de contratación.');
        }

        TipoEscrito::fromString($tipo);

        $result = ($this->generarEscritoPdf)(
            $expediente->id()->value(),
            $tipo,
            incluirMembrete: true,
            guardar: false,
        );

        return $result['pdfBinary'] ?? throw new \RuntimeException('No se pudo generar el documento.');
    }
}
