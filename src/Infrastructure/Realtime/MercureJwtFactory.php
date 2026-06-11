<?php

declare(strict_types=1);

namespace App\Infrastructure\Realtime;

final class MercureJwtFactory
{
    public function __construct(
        private string $mercureJwtSecret,
    ) {
    }

    /**
     * @param list<string> $publishTopics
     * @param list<string> $subscribeTopics
     */
    public function create(array $publishTopics = [], array $subscribeTopics = [], int $ttlSeconds = 3600): string
    {
        $mercure = [];
        if ([] !== $publishTopics) {
            $mercure['publish'] = $publishTopics;
        }
        if ([] !== $subscribeTopics) {
            $mercure['subscribe'] = $subscribeTopics;
        }

        $payload = [
            'mercure' => $mercure,
            'iat' => time(),
            'exp' => time() + $ttlSeconds,
        ];

        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], \JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode($payload, \JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header . '.' . $body, $this->mercureJwtSecret, true));

        return $header . '.' . $body . '.' . $signature;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
