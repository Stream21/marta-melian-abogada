<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\EscritoPdfGeneratorPort;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\EscritoBloqueValidator;
use App\Application\Service\EscritoDefaultPlantillaFactory;
use App\Application\Service\EscritoHtmlRenderer;
use App\Application\Service\EscritoVariableResolver;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\EscritoPlantillaRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteId;

final class GenerarEscritoPdfUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private EscritoPlantillaRepositoryInterface $plantillaRepository,
        private ObtenerDespachoConfigUseCase $obtenerDespacho,
        private EscritoVariableResolver $variableResolver,
        private EscritoHtmlRenderer $htmlRenderer,
        private EscritoPdfGeneratorPort $pdfGenerator,
        private EscritoBloqueValidator $bloqueValidator,
        private ExpedienteFileStoragePort $expedienteStorage,
    ) {
    }

    /**
     * @return array{pdfPath: string, filename: string, html: string, pdfBinary: string}
     */
    public function __invoke(
        string $expedienteId,
        string $tipo,
        bool $incluirMembrete,
        bool $guardar = true,
        ?string $clientSignatureRelativePath = null,
    ): array {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $tipoEscrito = TipoEscrito::fromString($tipo);
        $tramiteId = $expediente->tramiteId();
        if (null === $tramiteId) {
            throw new \InvalidArgumentException('El expediente no tiene trámite asociado.');
        }

        $tramite = $this->tramiteRepository->findById(new TramiteId($tramiteId));
        if (null === $tramite) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        $cliente = null;
        if (null !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
        }

        $despacho = ($this->obtenerDespacho)();
        $values = $this->variableResolver->resolve($despacho, $cliente, $tramite, $expediente);
        if (null !== $clientSignatureRelativePath && '' !== $clientSignatureRelativePath) {
            $values['FIRMA_CLIENTE_DATA_URI'] = $this->toDataUri(
                $this->expedienteStorage->getAbsolutePath($clientSignatureRelativePath),
            );
        }

        $plantilla = $this->plantillaRepository->findByTramiteAndTipo(new TramiteId($tramiteId), $tipoEscrito);
        $bloques = null !== $plantilla
            ? $this->bloqueValidator->normalizeLegacy($plantilla->bloques())
            : EscritoDefaultPlantillaFactory::createDefault($tipoEscrito);

        $html = $this->htmlRenderer->render($bloques, $values, $despacho, $incluirMembrete);
        $pdfBinary = $this->pdfGenerator->generateFromHtml($html);

        $filename = sprintf('%s_%s_%s.pdf', $tipoEscrito->value, $expediente->numero(), date('Ymd_His'));
        $pdfPath = '';

        if ($guardar) {
            $pdfPath = $this->expedienteStorage->savePdf(new ExpedienteId($expedienteId), $filename, $pdfBinary);
        }

        return [
            'pdfPath' => $pdfPath,
            'filename' => $filename,
            'html' => $html,
            'pdfBinary' => $pdfBinary,
        ];
    }

    private function toDataUri(string $absolutePath): string
    {
        if (!is_file($absolutePath)) {
            return '';
        }

        $mime = str_ends_with(strtolower($absolutePath), '.png') ? 'image/png' : 'image/jpeg';
        $encoded = base64_encode((string) file_get_contents($absolutePath));

        return 'data:' . $mime . ';base64,' . $encoded;
    }
}
