<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\EscritoLibrePdfService;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteEscritoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ActualizarEscritoExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteEscritoRepositoryInterface $escritoRepository,
        private EscritoLibrePdfService $pdfService,
    ) {
    }

    /**
     * @return array{id: string, titulo: string, pdfPath: string}
     */
    public function __invoke(string $expedienteId, string $escritoId, string $titulo, string $contenidoHtml): array
    {
        $titulo = trim($titulo);
        if (mb_strlen($titulo) < 2) {
            throw new \InvalidArgumentException('El título del escrito es obligatorio.');
        }

        $contenidoHtml = trim($contenidoHtml);
        if ('' === $contenidoHtml || '<p></p>' === $contenidoHtml) {
            throw new \InvalidArgumentException('El contenido del escrito no puede estar vacío.');
        }

        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Contratacion === $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('Los escritos no están disponibles en fase de contratación.');
        }

        $escrito = $this->escritoRepository->findById($escritoId);
        if (null === $escrito || !$escrito->expedienteId()->equals($id)) {
            throw new \InvalidArgumentException('Escrito no encontrado.');
        }

        $pdfPath = $this->pdfService->generar($expedienteId, $contenidoHtml);
        $actualizado = $escrito->withContenido($titulo, $contenidoHtml, $pdfPath);
        $this->escritoRepository->save($actualizado);

        return [
            'id' => $actualizado->id(),
            'titulo' => $actualizado->titulo(),
            'pdfPath' => $actualizado->pdfPath(),
        ];
    }
}
