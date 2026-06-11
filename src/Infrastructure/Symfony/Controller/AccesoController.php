<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\ClienteInput;
use App\Application\UseCase\ActualizarClienteIdentidadAccesoUseCase;
use App\Application\UseCase\CompletarPasoClienteUseCase;
use App\Application\UseCase\ExtraerDocumentoIdentidadUseCase;
use App\Application\UseCase\GenerarDocumentoFirmaAccesoUseCase;
use App\Application\UseCase\ObtenerAccesoExpedienteUseCase;
use App\Domain\Repository\ExpedienteRepositoryInterface;
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
        private ExtraerDocumentoIdentidadUseCase $extraerDocumento,
        private ActualizarClienteIdentidadAccesoUseCase $actualizarIdentidad,
        private ExpedienteRepositoryInterface $expedienteRepository,
    ) {
    }

    #[Route(path: '/{token}/extraer-documento', name: 'extraer_documento', methods: ['POST'])]
    public function extraerDocumento(string $token, Request $request): JsonResponse
    {
        if (!$this->tokenValido($token)) {
            return new JsonResponse(['message' => 'Enlace de acceso no válido.'], Response::HTTP_NOT_FOUND);
        }

        try {
            [$tipo, $anverso, $reverso] = $this->parseDocumentoUpload($request);

            return new JsonResponse(($this->extraerDocumento)($tipo, $anverso, $reverso));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{token}/datos-identidad', name: 'datos_identidad', methods: ['POST'])]
    public function datosIdentidad(string $token, Request $request): JsonResponse
    {
        try {
            [$tipo, $anverso, $reverso] = $this->parseDocumentoUpload($request);
            $datos = $request->request->all('datos');
            if (!is_array($datos)) {
                $datos = [];
            }

            ($this->actualizarIdentidad)($token, $tipo, $anverso, $reverso, $this->inputFromArray($datos));

            return new JsonResponse(($this->obtenerAcceso)($token));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
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

    private function tokenValido(string $token): bool
    {
        return null !== $this->expedienteRepository->findByAccessToken($token);
    }

    /**
     * @return array{0: string, 1: string, 2: string|null}
     */
    private function parseDocumentoUpload(Request $request): array
    {
        $tipo = (string) ($request->request->get('tipoEscaneo') ?? '');
        $anversoFile = $request->files->get('anverso');
        $reversoFile = $request->files->get('reverso');

        if (!is_object($anversoFile) || !method_exists($anversoFile, 'getContent')) {
            throw new \InvalidArgumentException('Debe adjuntar la imagen del anverso del documento.');
        }

        $anverso = (string) $anversoFile->getContent();
        $reverso = null;
        if (is_object($reversoFile) && method_exists($reversoFile, 'getContent')) {
            $reverso = (string) $reversoFile->getContent();
        }

        return [$tipo, $anverso, $reverso];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function inputFromArray(array $data): ClienteInput
    {
        return new ClienteInput(
            (string) ($data['nombre'] ?? ''),
            (string) ($data['nacionalidad'] ?? ''),
            (string) ($data['tipoDocumento'] ?? ''),
            (string) ($data['numDocumento'] ?? ''),
            isset($data['fechaNacimiento']) ? (string) $data['fechaNacimiento'] : null,
            (string) ($data['lugarNacimiento'] ?? ''),
            (string) ($data['estadoCivil'] ?? ''),
            (string) ($data['domicilio'] ?? ''),
            (string) ($data['codigoPostal'] ?? ''),
            (string) ($data['ciudad'] ?? ''),
            (string) ($data['provincia'] ?? ''),
            (string) ($data['nombrePadre'] ?? ''),
            (string) ($data['nombreMadre'] ?? ''),
            (string) ($data['telefono'] ?? ''),
            (string) ($data['email'] ?? ''),
        );
    }
}
