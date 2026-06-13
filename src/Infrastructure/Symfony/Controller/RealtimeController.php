<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Infrastructure\Realtime\MercureJwtFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/realtime', name: 'api_realtime_')]
final class RealtimeController extends AbstractController
{
    public function __construct(
        private MercureJwtFactory $jwtFactory,
        private string $mercurePublicUrl,
    ) {
    }

    #[Route(path: '/mercure-token/{expedienteId}', name: 'mercure_token', methods: ['GET'])]
    public function mercureToken(string $expedienteId): JsonResponse
    {
        if ('' === trim($this->mercurePublicUrl)) {
            return new JsonResponse(['message' => 'Mercure no configurado.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $topic = sprintf('/expedientes/%s/contratacion', $expedienteId);
        $topics = [$topic, '/abogado/notificaciones'];
        $token = $this->jwtFactory->create(subscribeTopics: $topics);

        return new JsonResponse([
            'token' => $token,
            'hubUrl' => rtrim($this->mercurePublicUrl, '/') . '/.well-known/mercure',
            'topic' => $topic,
            'topics' => $topics,
        ]);
    }

    #[Route(path: '/mercure-token-abogado', name: 'mercure_token_abogado', methods: ['GET'])]
    public function mercureTokenAbogado(): JsonResponse
    {
        if ('' === trim($this->mercurePublicUrl)) {
            return new JsonResponse(['message' => 'Mercure no configurado.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $topic = '/abogado/notificaciones';
        $token = $this->jwtFactory->create(subscribeTopics: [$topic]);

        return new JsonResponse([
            'token' => $token,
            'hubUrl' => rtrim($this->mercurePublicUrl, '/') . '/.well-known/mercure',
            'topic' => $topic,
        ]);
    }
}
