<?php

declare(strict_types=1);

namespace App\Infrastructure\Realtime;

use App\Application\Port\ContratacionRealtimePort;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MercureContratacionPublisher implements ContratacionRealtimePort
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private MercureJwtFactory $jwtFactory,
        private string $mercureHubUrl,
        private LoggerInterface $logger,
    ) {
    }

    public function publishContratacionUpdate(string $expedienteId, array $payload): void
    {
        if ('' === trim($this->mercureHubUrl)) {
            return;
        }

        $topic = sprintf('/expedientes/%s/contratacion', $expedienteId);

        try {
            $this->publicarConTimeout($topic, $payload);
            $this->publicarConTimeout('/abogado/notificaciones', array_merge($payload, ['expedienteId' => $expedienteId]));
        } catch (\Throwable $e) {
            $this->logger->warning('No se pudo publicar evento Mercure: {message}', [
                'message' => $e->getMessage(),
                'expedienteId' => $expedienteId,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function publicarConTimeout(string $topic, array $payload): void
    {
        $response = $this->httpClient->request('POST', rtrim($this->mercureHubUrl, '/') . '/.well-known/mercure', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwtFactory->create(['*']),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'topic' => $topic,
                'data' => json_encode($payload, \JSON_THROW_ON_ERROR),
            ],
            'timeout' => 3,
            'max_duration' => 3,
        ]);

        // Consumir la respuesta para no bloquear el cierre de la petición HTTP principal.
        $response->getStatusCode();
    }
}
