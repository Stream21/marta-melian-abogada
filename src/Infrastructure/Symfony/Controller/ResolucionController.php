<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\UseCase\ActualizarGestionesResolucionUseCase;
use App\Application\UseCase\ArchivarExpedienteDesdeResolucionUseCase;
use App\Application\UseCase\ObtenerResolucionExpedienteUseCase;
use App\Application\UseCase\ProgramarRecordatorioFuturoUseCase;
use App\Application\UseCase\RegistrarResolucionUseCase;
use App\Domain\Repository\ExpedienteResolucionRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Infrastructure\Http\UploadedFileMimeDetector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/expedientes/{id}/resolucion', name: 'api_expedientes_resolucion_')]
#[IsGranted('ROLE_USER')]
final class ResolucionController extends AbstractController
{
    public function __construct(
        private ObtenerResolucionExpedienteUseCase $obtener,
        private RegistrarResolucionUseCase $registrar,
        private ActualizarGestionesResolucionUseCase $actualizarGestiones,
        private ProgramarRecordatorioFuturoUseCase $programarRecordatorio,
        private ArchivarExpedienteDesdeResolucionUseCase $archivar,
        private ExpedienteResolucionRepositoryInterface $resolucionRepository,
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

    #[Route(path: '', name: 'registrar', methods: ['POST'])]
    public function registrar(string $id, Request $request): JsonResponse
    {
        try {
            $archivo = $this->requireUploadedFile($request, 'resolucion');
            ($this->registrar)(
                $id,
                $archivo,
                (string) $request->request->get('outcome', ''),
                (string) $request->request->get('fechaNotificacion', ''),
            );

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/gestiones', name: 'gestiones', methods: ['PATCH', 'POST'])]
    public function gestiones(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = [];
        }
        $updates = $data['gestiones'] ?? [];
        if (!is_array($updates)) {
            $updates = [];
        }

        try {
            ($this->actualizarGestiones)($id, $updates);

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/recordatorio', name: 'recordatorio', methods: ['POST'])]
    public function recordatorio(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = $request->request->all();
        }

        try {
            ($this->programarRecordatorio)(
                $id,
                (string) ($data['fecha'] ?? ''),
                (string) ($data['servicioId'] ?? ''),
                (string) ($data['tramiteId'] ?? ''),
            );

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/archivar', name: 'archivar', methods: ['POST'])]
    public function archivar(string $id): JsonResponse
    {
        try {
            ($this->archivar)($id);

            return new JsonResponse(['message' => 'Expediente archivado.']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/archivo', name: 'archivo', methods: ['GET'])]
    public function descargar(string $id): Response
    {
        $resolucion = $this->resolucionRepository->findByExpediente(new ExpedienteId($id));
        if (null === $resolucion) {
            return new JsonResponse(['message' => 'Resolución no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $content = $this->fileStorage->readRelativePath($resolucion->resolucionPath());
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        $absolute = $this->fileStorage->getAbsolutePath($resolucion->resolucionPath());

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => $this->mimeDetector->detectFromPath($absolute),
            'Content-Disposition' => 'inline; filename="resolucion.pdf"',
        ]);
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
}
