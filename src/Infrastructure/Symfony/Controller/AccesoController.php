<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\CompletarPasoClienteUseCase;
use App\Application\UseCase\GenerarDocumentoFirmaAccesoUseCase;
use App\Application\UseCase\ObtenerAccesoExpedienteUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/acceso', name: 'api_acceso_')]
final class AccesoController extends AbstractController
{
    public function __construct(
        private ObtenerAccesoExpedienteUseCase $obtenerAcceso,
        private CompletarPasoClienteUseCase $completarPaso,
        private GenerarDocumentoFirmaAccesoUseCase $generarDocumentoFirma,
    ) {
    }

    #[Route(path: '/{token}', name: 'show', methods: ['GET'])]
    public function show(string $token): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtenerAcceso)($token));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/{token}/completar-paso', name: 'completar_paso', methods: ['POST'])]
    public function completarPaso(string $token, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $paso = (string) ($data['paso'] ?? '');

        try {
            ($this->completarPaso)($token, $paso);

            return new JsonResponse(($this->obtenerAcceso)($token));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{token}/firma/{tipo}/pdf', name: 'firma_pdf', methods: ['GET'])]
    public function firmaPdf(string $token, string $tipo): Response
    {
        try {
            $pdf = ($this->generarDocumentoFirma)($token, $tipo);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="documento-' . $tipo . '.pdf"',
        ]);
    }
}
