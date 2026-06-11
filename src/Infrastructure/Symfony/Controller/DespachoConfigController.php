<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\ActualizarDespachoConfigInput;
use App\Application\UseCase\ActualizarDespachoConfigUseCase;
use App\Application\UseCase\ObtenerDespachoConfigUseCase;
use App\Application\UseCase\SubirImagenDespachoUseCase;
use App\Domain\Entity\DespachoConfig;
use App\Infrastructure\Http\UploadedFileMimeDetector;
use App\Infrastructure\Storage\DespachoFileStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/config/despacho', name: 'api_config_despacho_')]
final class DespachoConfigController extends AbstractController
{
    public function __construct(
        private ObtenerDespachoConfigUseCase $obtener,
        private ActualizarDespachoConfigUseCase $actualizar,
        private SubirImagenDespachoUseCase $subirImagen,
        private DespachoFileStorage $storage,
        private UploadedFileMimeDetector $mimeDetector,
    ) {
    }

    #[Route(path: '', name: 'get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return new JsonResponse($this->toArray(($this->obtener)()));
    }

    #[Route(path: '', name: 'put', methods: ['PUT'])]
    public function put(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['message' => 'JSON inválido.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $config = ($this->actualizar)(new ActualizarDespachoConfigInput(
                (string) ($data['nombreFirma'] ?? ''),
                (string) ($data['nombreLetrada'] ?? ''),
                (string) ($data['numColegiado'] ?? ''),
                (string) ($data['direccion'] ?? ''),
                (string) ($data['ciudad'] ?? ''),
                (string) ($data['subtituloProfesional'] ?? ''),
                (string) ($data['telefono'] ?? ''),
                (string) ($data['email'] ?? ''),
                (string) ($data['web'] ?? ''),
                (string) ($data['nif'] ?? ''),
                (string) ($data['colegioAbogados'] ?? ''),
                (string) ($data['iban'] ?? ''),
                (string) ($data['entidadBancaria'] ?? ''),
                (string) ($data['titularCuenta'] ?? ''),
                isset($data['cabeceraHtml']) ? (string) $data['cabeceraHtml'] : null,
                isset($data['pieHtml']) ? (string) $data['pieHtml'] : null,
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->toArray($config));
    }

    #[Route(path: '/logo', name: 'logo', methods: ['POST'])]
    public function uploadLogo(Request $request): JsonResponse
    {
        return $this->handleUpload($request, 'logo');
    }

    #[Route(path: '/sello', name: 'sello', methods: ['POST'])]
    public function uploadSello(Request $request): JsonResponse
    {
        return $this->handleUpload($request, 'sello');
    }

    #[Route(path: '/assets/{tipo}', name: 'asset', methods: ['GET'])]
    public function asset(string $tipo): Response
    {
        if (!in_array($tipo, ['logo', 'sello'], true)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $config = ($this->obtener)();
        $path = 'logo' === $tipo ? $config->logoPath() : $config->selloPath();
        if (null === $path) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $fullPath = $this->storage->getAbsolutePath($path);
        if (!is_file($fullPath)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse($fullPath, 200, [
            'Content-Type' => $this->mimeDetector->detectFromPath($fullPath),
        ]);
    }

    private function handleUpload(Request $request, string $tipo): JsonResponse
    {
        $file = $request->files->get('file');
        if (null === $file) {
            return new JsonResponse(['message' => 'No se recibió ningún archivo.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $content = (string) file_get_contents($file->getPathname());
            $mimeType = $this->mimeDetector->detect($file);

            $config = ($this->subirImagen)($tipo, $content, $mimeType);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['message' => 'No se pudo procesar la imagen.'],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return new JsonResponse($this->toArray($config));
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(DespachoConfig $config): array
    {
        return [
            'id' => $config->id(),
            'nombreFirma' => $config->nombreFirma(),
            'nombreLetrada' => $config->nombreLetrada(),
            'numColegiado' => $config->numColegiado(),
            'direccion' => $config->direccion(),
            'ciudad' => $config->ciudad(),
            'subtituloProfesional' => $config->subtituloProfesional(),
            'telefono' => $config->telefono(),
            'email' => $config->email(),
            'web' => $config->web(),
            'nif' => $config->nif(),
            'colegioAbogados' => $config->colegioAbogados(),
            'iban' => $config->iban(),
            'entidadBancaria' => $config->entidadBancaria(),
            'titularCuenta' => $config->titularCuenta(),
            'cabeceraHtml' => $config->cabeceraHtml(),
            'pieHtml' => $config->pieHtml(),
            'logoUrl' => null !== $config->logoPath() ? '/api/config/despacho/assets/logo' : null,
            'selloUrl' => null !== $config->selloPath() ? '/api/config/despacho/assets/sello' : null,
            'updatedAt' => $config->updatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
