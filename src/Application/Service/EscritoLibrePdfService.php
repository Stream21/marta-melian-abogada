<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\EscritoPdfGeneratorPort;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\UseCase\ObtenerDespachoConfigUseCase;
use App\Domain\Entity\DespachoConfig;
use App\Domain\Entity\Expediente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteId;

final class EscritoLibrePdfService
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private ObtenerDespachoConfigUseCase $obtenerDespacho,
        private EscritoVariableResolver $variableResolver,
        private EscritoHtmlRenderer $htmlRenderer,
        private EscritoPdfGeneratorPort $pdfGenerator,
        private ExpedienteFileStoragePort $fileStorage,
    ) {
    }

    public function generar(string $expedienteId, string $contenidoHtml): string
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $cliente = null;
        if (null !== $expediente->clienteId() && '' !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
        }

        $tramite = null;
        if (null !== $expediente->tramiteId() && '' !== $expediente->tramiteId()) {
            $tramite = $this->tramiteRepository->findById(new TramiteId($expediente->tramiteId()));
        }

        $despacho = ($this->obtenerDespacho)();
        $variables = $this->variableResolver->resolve($despacho, $cliente, $tramite, $expediente);
        $variables['CLIENTE_NOMBRE'] = $variables['NOMBRE_CLIENTE'] ?? '';
        $variables['EXPEDIENTE_NUMERO'] = $variables['REFERENCIA_EXPEDIENTE'] ?? '';
        $variables['FECHA_HOY'] = $variables['FECHA_ACTUAL'] ?? date('d/m/Y');
        $html = $this->renderHtml($contenidoHtml, $variables, $despacho);
        $pdfBinary = $this->pdfGenerator->generateFromHtml($html);
        $filename = sprintf('escritos/escrito_%s.pdf', date('YmdHis'));

        return $this->fileStorage->savePdf($expediente->id(), $filename, $pdfBinary);
    }

    /**
     * @param array<string, string> $variables
     */
    private function renderHtml(string $contenidoHtml, array $variables, ?DespachoConfig $despacho): string
    {
        $contenido = $this->variableResolver->substitute($contenidoHtml, $variables);

        $bloques = [
            ['type' => 'html', 'html' => $contenido],
            ['type' => 'signature_lawyer'],
        ];

        return $this->htmlRenderer->render($bloques, $variables, $despacho, true);
    }
}
