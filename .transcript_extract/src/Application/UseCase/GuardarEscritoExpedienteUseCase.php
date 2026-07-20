<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\EscritoLibrePdfService;
use App\Application\Service\RequerimientosCompletitudValidator;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteEscrito;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteEscritoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class GuardarEscritoExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteEscritoRepositoryInterface $escritoRepository,
        private EscritoLibrePdfService $escritoLibrePdf,
        private RequerimientosCompletitudValidator $completitudValidator,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    /**
     * @return array{id: string, titulo: string, pdfPath: string}
     */
    public function __invoke(string $expedienteId, string $titulo, string $contenidoHtml): array
    {
        $id = new ExpedienteId($expedienteId);
        $this->completitudValidator->assertEnRequerimientos($id);

        $titulo = trim($titulo);
        if ('' === $titulo) {
            throw new \InvalidArgumentException('El título del escrito es obligatorio.');
        }

        $contenidoHtml = trim($contenidoHtml);
        if ('' === $contenidoHtml) {
            throw new \InvalidArgumentException('El contenido del escrito no puede estar vacío.');
        }

        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $result = $this->escritoLibrePdf->generar($expedienteId, $titulo, $contenidoHtml);
        $escritoId = bin2hex(random_bytes(16));
        $now = new \DateTimeImmutable('now');

        $this->escritoRepository->save(new ExpedienteEscrito(
            $escritoId,
            $id,
            $titulo,
            $contenidoHtml,
            $result['pdfPath'],
            $now,
        ));

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'escrito_generado',
            sprintf('El abogado ha generado el escrito «%s».', $titulo),
            ActorHitoExpediente::Abogado,
            $now,
        ));

        $this->realtime->publishContratacionUpdate($id->value(), [
            'type' => 'escrito_generado',
            'escritoId' => $escritoId,
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);

        return [
            'id' => $escritoId,
            'titulo' => $titulo,
            'pdfPath' => $result['pdfPath'],
        ];
    }
}
