<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ActualizarDespachoConfigInput;
use App\Domain\Entity\DespachoConfig;
use App\Domain\Repository\DespachoConfigRepositoryInterface;

final class ActualizarDespachoConfigUseCase
{
    public function __construct(
        private DespachoConfigRepositoryInterface $repository,
        private ObtenerDespachoConfigUseCase $obtener,
    ) {
    }

    public function __invoke(ActualizarDespachoConfigInput $input): DespachoConfig
    {
        $nombreFirma = trim($input->nombreFirma);
        $nombreLetrada = trim($input->nombreLetrada);
        $numColegiado = trim($input->numColegiado);
        $direccion = trim($input->direccion);
        $ciudad = trim($input->ciudad);

        if ('' === $nombreFirma || '' === $nombreLetrada || '' === $numColegiado || '' === $direccion || '' === $ciudad) {
            throw new \InvalidArgumentException('Los campos básicos del despacho son obligatorios.');
        }

        $current = ($this->obtener)();
        $updated = $current->withDatos(
            $nombreFirma,
            $nombreLetrada,
            $numColegiado,
            $direccion,
            $ciudad,
            trim($input->subtituloProfesional),
            trim($input->telefono),
            trim($input->email),
            trim($input->web),
            trim($input->nif),
            trim($input->colegioAbogados),
            trim($input->iban),
            trim($input->entidadBancaria),
            trim($input->titularCuenta),
            $this->normalizeHtml($input->cabeceraHtml),
            $this->normalizeHtml($input->pieHtml),
        );
        $this->repository->save($updated);

        return ($this->obtener)();
    }

    private function normalizeHtml(?string $html): ?string
    {
        if (null === $html) {
            return null;
        }

        $trimmed = trim($html);

        return '' === $trimmed ? null : $trimmed;
    }
}
