<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\UseCase\AgregarRequerimientoMercurioUseCase;
use App\Application\UseCase\AvanzarResolucionUseCase;
use App\Application\UseCase\ObtenerTramitacionExpedienteUseCase;
use App\Application\UseCase\PresentarRequerimientoMercurioUseCase;
use App\Application\UseCase\RegistrarPresentacionTelematicaUseCase;
use App\Application\UseCase\RegistrarSeguimientoExtranjeriaUseCase;
use App\Application\UseCase\SubirArchivoRequerimientoMercurioUseCase;
use App\Application\UseCase\VincularEscritoRequerimientoMercurioUseCase;
use App\Domain\Repository\ExpedientePresentacionTelematicaRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;
use App\Infrastructure\Http\UploadedFileMimeDetector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/expedientes/{id}/tramitacion', name: 'api_expedientes_tramitacion_')]
#[IsGranted('ROLE_USER')]
final class TramitacionController extends AbstractController
{
    public function __construct(
        private ObtenerTramitacionExpedienteUseCase $obtener,
        private RegistrarPresentacionTelematicaUseCase $registrarPresentacion,
        private RegistrarSeguimientoExtranjeriaUseCase $registrarSeguimiento,
        private AgregarRequerimientoMercurioUseCase $agregarRequerimiento,
        private SubirArchivoRequerimientoMercurioUseCase $subirArchivo,
        private PresentarRequerimientoMercurioUseCase $presentarRequerimiento,
        private VincularEscritoRequerimientoMercurioUseCase $vincularEscrito,
        private AvanzarResolucionUseCase $avanzarResolucion,
        private ExpedientePresentacionTelematicaRepositoryInterface $presentacionRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private UploadedFileMimeDetector $mimeDetector,
    ) {
    }

    #[Route(path: '', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/presentacion', name: 'presentacion', methods: ['POST'])]
    public function presentacion(string $id, Request $request): JsonResponse
    {
        try {
            $presentacion = $this->requireUploadedFile($request, 'presentacion');
            $justificante = $this->requireUploadedFile($request, 'justificante');

            ($this->registrarPresentacion)(
                $id,
                $presentacion,
                $justificante,
                (string) $request->request->get('fechaPresentacion', ''),
            );

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/seguimiento', name: 'seguimiento', methods: ['PATCH', 'POST'])]
    public function seguimiento(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = $request->request->all();
        }

        try {
            ($this->registrarSeguimiento)(
                $id,
                (string) ($data['numeroExpedienteExtranjeria'] ?? ''),
            );

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos', name: 'requerimientos_crear', methods: ['POST'])]
    public function crearRequerimiento(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $reqId = ($this->agregarRequerimiento)(
                $id,
                (string) ($data['tipo'] ?? 'documento'),
                (string) ($data['destino'] ?? 'despacho'),
                (string) ($data['nombre'] ?? ''),
                (string) ($data['descripcion'] ?? ''),
            );

            return new JsonResponse(['id' => $reqId, ...($this->obtener)($id)], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/archivo', name: 'requerimiento_archivo', methods: ['POST'])]
    public function subirArchivoRequerimiento(string $id, string $reqId, Request $request): JsonResponse
    {
        try {
            $file = $this->requireUploadedFile($request, 'archivo');
            ($this->subirArchivo)($id, $reqId, $file, false);

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/presentar', name: 'requerimiento_presentar', methods: ['POST'])]
    public function presentarRequerimiento(string $id, string $reqId, Request $request): JsonResponse
    {
        try {
            $justificante = $this->requireUploadedFile($request, 'justificante');
            $archivo = null;
            if ($request->files->has('archivo') && null !== $request->files->get('archivo')) {
                $archivo = $this->requireUploadedFile($request, 'archivo');
            }
            ($this->presentarRequerimiento)($id, $reqId, $justificante, $archivo);

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/escrito', name: 'requerimiento_escrito', methods: ['POST'])]
    public function vincularEscrito(string $id, string $reqId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            ($this->vincularEscrito)($id, $reqId, (string) ($data['escritoId'] ?? ''));

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/avanzar-resolucion', name: 'avanzar_resolucion', methods: ['POST'])]
    public function avanzarResolucion(string $id): JsonResponse
    {
        try {
            ($this->avanzarResolucion)($id);

            return new JsonResponse(['message' => 'Expediente pasado a resolución.']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/presentacion/archivo/{tipo}', name: 'presentacion_archivo', methods: ['GET'], requirements: ['tipo' => 'presentacion|justificante'])]
    public function descargarPresentacion(string $id, string $tipo): Response
    {
        $presentacion = $this->presentacionRepository->findByExpediente(new ExpedienteId($id));
        if (null === $presentacion) {
            return new JsonResponse(['message' => 'Presentación no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $path = 'presentacion' === $tipo
            ? $presentacion->presentacionPath()
            : $presentacion->justificantePath();

        return $this->fileResponse($path, $tipo . '.pdf');
    }

    #[Route(path: '/requerimientos/{reqId}/archivo-descarga', name: 'requerimiento_archivo_get', methods: ['GET'])]
    public function descargarArchivoRequerimiento(string $id, string $reqId): Response
    {
        $req = $this->requerimientoRepository->findById(new ExpedienteRequerimientoMercurioId($reqId));
        if (null === $req || $req->expedienteId()->value() !== $id || null === $req->archivoPath()) {
            return new JsonResponse(['message' => 'Archivo no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        return $this->fileResponse($req->archivoPath(), $req->archivoNombre() ?? 'documento.pdf');
    }

    #[Route(path: '/requerimientos/{reqId}/justificante', name: 'requerimiento_justificante_get', methods: ['GET'])]
    public function descargarJustificanteRequerimiento(string $id, string $reqId): Response
    {
        $req = $this->requerimientoRepository->findById(new ExpedienteRequerimientoMercurioId($reqId));
        if (null === $req || $req->expedienteId()->value() !== $id || null === $req->justificantePresentacionPath()) {
            return new JsonResponse(['message' => 'Justificante no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        return $this->fileResponse($req->justificantePresentacionPath(), 'justificante.pdf');
    }

    /**
     * @return array{content: string, filename: string}
     */
    private function requireUploadedFile(Request $request, string $key): array
    {
        $file = $request->files->get($key);
        if (!is_object($file) || !method_exists($file, 'getContent')) {
            throw new \InvalidArgumentException(sprintf('Falta el archivo «%s».', $key));
        }

        $content = (string) $file->getContent();
        if ('' === $content) {
            throw new \InvalidArgumentException(sprintf('El archivo «%s» está vacío.', $key));
        }

        $filename = method_exists($file, 'getClientOriginalName')
            ? (string) $file->getClientOriginalName()
            : $key . '.pdf';

        return ['content' => $content, 'filename' => $filename];
    }

    private function fileResponse(string $relativePath, string $filename): Response
    {
        try {
            $content = $this->fileStorage->readRelativePath($relativePath);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        $absolute = $this->fileStorage->getAbsolutePath($relativePath);

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => $this->mimeDetector->detectFromPath($absolute),
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }
}
