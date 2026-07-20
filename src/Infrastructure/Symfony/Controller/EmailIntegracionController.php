<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\ProbarEmailUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/integraciones/email', name: 'api_integraciones_email_')]
#[IsGranted('ROLE_USER')]
final class EmailIntegracionController extends AbstractController
{
    public function __construct(
        private ProbarEmailUseCase $probarEmail,
    ) {
    }

    #[Route(path: '/estado', name: 'estado', methods: ['GET'])]
    public function estado(): JsonResponse
    {
        return new JsonResponse($this->probarEmail->estado());
    }

    #[Route(path: '/probar', name: 'probar', methods: ['POST'])]
    public function probar(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $result = ($this->probarEmail)(
                (string) ($data['email'] ?? ''),
                isset($data['asunto']) ? (string) $data['asunto'] : null,
                isset($data['mensaje']) ? (string) $data['mensaje'] : null,
            );

            return new JsonResponse($result);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
