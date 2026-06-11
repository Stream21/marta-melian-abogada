<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\EscritoPdfGeneratorPort;
use App\Application\Service\EscritoBloqueValidator;
use App\Application\Service\EscritoHtmlRenderer;
use App\Application\Service\EscritoVariableResolver;
use App\Domain\Repository\DespachoConfigRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\TramiteId;

final class GenerarEscritoPdfPreviewUseCase
{
    public function __construct(
        private TramiteRepositoryInterface $tramiteRepository,
        private DespachoConfigRepositoryInterface $despachoRepository,
        private EscritoBloqueValidator $bloqueValidator,
        private EscritoVariableResolver $variableResolver,
        private EscritoHtmlRenderer $htmlRenderer,
        private EscritoPdfGeneratorPort $pdfGenerator,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $bloques
     */
    public function __invoke(string $tramiteId, array $bloques, bool $incluirMembrete): string
    {
        $id = new TramiteId($tramiteId);
        $tramite = $this->tramiteRepository->findById($id);
        if (null === $tramite) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        $bloques = $this->bloqueValidator->validateAndNormalize($bloques);
        $despacho = $this->despachoRepository->find();
        $variables = $this->variableResolver->previewValues($despacho, $tramite);
        $html = $this->htmlRenderer->render($bloques, $variables, $despacho, $incluirMembrete);

        return $this->pdfGenerator->generateFromHtml($html);
    }
}
