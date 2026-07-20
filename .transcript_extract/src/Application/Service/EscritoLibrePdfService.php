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
        private ExpedienteFileStoragePort $expedienteStorage,
        private string $projectDir,
    ) {
    }

    /**
     * @return array{pdfPath: string, pdfBinary: string}
     */
    public function generar(string $expedienteId, string $titulo, string $contenidoHtml): array
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $despacho = ($this->obtenerDespacho)();
        $cliente = null;
        $tramite = null;
        if (null !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
        }
        if (null !== $expediente->tramiteId()) {
            $tramite = $this->tramiteRepository->findById(new TramiteId($expediente->tramiteId()));
        }

        $variables = $this->variableResolver->resolve($despacho, $cliente, $tramite, $expediente);
        $contenidoHtml = $this->substituirVariables($contenidoHtml, $variables);

        $bloques = [
            ['type' => 'title', 'text' => $titulo, 'align' => 'center', 'fontSize' => '16'],
            ['type' => 'text', 'text' => $contenidoHtml, 'align' => 'left'],
            [
                'type' => 'columns',
                'columns' => [
                    ['width' => '50', 'children' => []],
                    [
                        'width' => '50',
                        'children' => [
                            ['type' => 'signature_lawyer'],
                        ],
                    ],
                ],
            ],
        ];

        $html = $this->htmlRenderer->render($bloques, $variables, $despacho, true);
        $pdfBinary = $this->pdfGenerator->generate($html);
        $filename = sprintf('escritos/escrito_%s_%s.pdf', preg_replace('/[^a-z0-9]+/i', '_', $titulo), date('YmdHis'));
        $pdfPath = $this->expedienteStorage->savePdf(new ExpedienteId($expedienteId), $filename, $pdfBinary);

        return ['pdfPath' => $pdfPath, 'pdfBinary' => $pdfBinary];
    }

    /**
     * @param array<string, string> $variables
     */
    private function substituirVariables(string $html, array $variables): string
    {
        $result = $html;
        foreach ($variables as $key => $value) {
            $result = str_replace('[[' . $key . ']]', htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'), $result);
        }

        return $result;
    }
}
