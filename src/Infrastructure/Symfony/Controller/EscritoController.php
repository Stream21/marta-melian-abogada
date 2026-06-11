<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\GuardarEscritoPlantillaInput;
use App\Application\Service\EscritoVariableCatalog;
use App\Application\UseCase\GenerarEscritoPdfPreviewUseCase;
use App\Application\UseCase\GuardarEscritoPlantillaUseCase;
use App\Application\UseCase\ObtenerEscritoPlantillaUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'api_escrito_')]
final class EscritoController extends AbstractController
{
    public function __construct(
        private EscritoVariableCatalog $variableCatalog,
        private ObtenerEscritoPlantillaUseCase $obtenerPlantilla,
        private GuardarEscritoPlantillaUseCase $guardarPlantilla,
        private GenerarEscritoPdfPreviewUseCase $generarPdfPreview,
    ) {
    }

    #[Route(path: '/api/escritos/variables', name: 'variables', methods: ['GET'])]
    public function variables(): JsonResponse
    {
        return new JsonResponse($this->variableCatalog->all());
    }

    #[Route(path: '/api/tramites/{tramiteId}/escritos/{tipo}', name: 'get', methods: ['GET'])]
    public function get(string $tramiteId, string $tipo): JsonResponse
    {
        try {
            $result = ($this->obtenerPlantilla)($tramiteId, $tipo);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result);
    }

    #[Route(path: '/api/tramites/{tramiteId}/escritos/{tipo}', name: 'put', methods: ['PUT'])]
    public function put(string $tramiteId, string $tipo, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['bloques']) || !is_array($data['bloques'])) {
            return new JsonResponse(['message' => 'Se requiere un array "bloques".'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = ($this->guardarPlantilla)(new GuardarEscritoPlantillaInput(
                $tramiteId,
                $tipo,
                $data['bloques'],
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result);
    }

    #[Route(path: '/api/tramites/{tramiteId}/escritos/{tipo}/pdf-preview', name: 'pdf_preview', methods: ['POST'])]
    public function pdfPreview(string $tramiteId, string $tipo, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['bloques']) || !is_array($data['bloques'])) {
            return new JsonResponse(['message' => 'Se requiere un array "bloques".'], Response::HTTP_BAD_REQUEST);
        }

        $incluirMembrete = !array_key_exists('incluirMembrete', $data) || (bool) $data['incluirMembrete'];

        try {
            $pdf = ($this->generarPdfPreview)($tramiteId, $data['bloques'], $incluirMembrete);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="escrito-' . $tipo . '.pdf"',
        ]);
    }
}
