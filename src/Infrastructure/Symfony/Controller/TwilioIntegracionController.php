<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\EnviarEnlaceExpedienteUseCase;
use App\Application\UseCase\ProbarTwilioUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/integraciones/twilio', name: 'api_integraciones_twilio_')]
#[IsGranted('ROLE_USER')]
final class TwilioIntegracionController extends AbstractController
{
    public function __construct(
        private ProbarTwilioUseCase $probarTwilio,
        private EnviarEnlaceExpedienteUseCase $enviarEnlace,
    ) {
    }

    #[Route(path: '/estado', name: 'estado', methods: ['GET'])]
    public function estado(): JsonResponse
    {
        return new JsonResponse($this->probarTwilio->estado());
    }

    #[Route(path: '/probar', name: 'probar', methods: ['POST'])]
    public function probar(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $result = ($this->probarTwilio)(
                (string) ($data['canal'] ?? ''),
                (string) ($data['telefono'] ?? ''),
                isset($data['mensaje']) ? (string) $data['mensaje'] : null,
            );

            return new JsonResponse($result);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    #[Route(path: '/expedientes/{id}/enviar-enlace', name: 'enviar_enlace', methods: ['POST'])]
    public function enviarEnlace(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $canales = is_array($data['canales'] ?? null) ? $data['canales'] : [];
        $canales = array_values(array_filter(
            array_map('strval', $canales),
            fn (string $c) => in_array($c, ['whatsapp', 'email'], true),
        ));

        try {
            $resultado = ($this->enviarEnlace)($id, $canales);

            return new JsonResponse(['canalesEnviados' => $resultado]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
