<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\EmailPort;
use App\Application\Port\TwilioPort;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;
use Psr\Log\LoggerInterface;

/**
 * Envío multi-canal al cliente respetando la preferencia del expediente.
 */
final class NotificarClienteCanalRouter
{
    public function __construct(
        private TwilioPort $twilioPort,
        private EmailPort $emailPort,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param list<string>|null $canalesOverride
     *
     * @return list<string> Canales enviados con éxito (whatsapp, email)
     */
    public function enviar(
        Expediente $expediente,
        Cliente $cliente,
        string $asunto,
        string $mensaje,
        ?array $canalesOverride = null,
        string $contexto = 'notificacion',
    ): array {
        $preferidos = $this->resolverCanales($expediente, $cliente, $canalesOverride);
        if ([] === $preferidos) {
            $this->logger->warning('No se notificó al cliente: sin canales disponibles.', [
                'expediente' => $expediente->numero(),
                'contexto' => $contexto,
            ]);

            return [];
        }

        $enviados = [];
        $telefono = trim($cliente->telefono());
        $email = trim($cliente->email());
        $numero = $expediente->numero();

        if (in_array('whatsapp', $preferidos, true) && '' !== $telefono) {
            if (!$this->twilioPort->isWhatsAppConfigured()) {
                $this->logger->info('WhatsApp no configurado; se omite el aviso.', [
                    'expediente' => $numero,
                    'contexto' => $contexto,
                ]);
            } else {
                try {
                    $this->twilioPort->sendWhatsAppMessage($telefono, $mensaje);
                    $enviados[] = 'whatsapp';
                } catch (\Throwable $e) {
                    $this->logger->error('Error enviando WhatsApp al cliente', [
                        'telefono' => $telefono,
                        'expediente' => $numero,
                        'contexto' => $contexto,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if (in_array('email', $preferidos, true) && '' !== $email) {
            if (!$this->emailPort->isConfigured()) {
                $this->logger->info('Email no configurado; se omite el aviso.', [
                    'expediente' => $numero,
                    'contexto' => $contexto,
                ]);
            } else {
                try {
                    $this->emailPort->send($email, $asunto, $mensaje);
                    $enviados[] = 'email';
                } catch (\Throwable $e) {
                    $this->logger->error('Error enviando email al cliente', [
                        'email' => $email,
                        'expediente' => $numero,
                        'contexto' => $contexto,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ([] === $enviados) {
            $this->logger->warning('No se pudo notificar al cliente (canales fallidos o sin dato).', [
                'expediente' => $numero,
                'contexto' => $contexto,
                'preferidos' => $preferidos,
                'tieneTelefono' => '' !== $telefono,
                'tieneEmail' => '' !== $email,
            ]);
        }

        return $enviados;
    }

    /**
     * @param list<string>|null $canalesOverride
     *
     * @return list<string>
     */
    public function resolverCanales(
        Expediente $expediente,
        Cliente $cliente,
        ?array $canalesOverride = null,
    ): array {
        if (null !== $canalesOverride) {
            return Expediente::normalizarCanales($canalesOverride);
        }

        $preferidos = $expediente->canalesNotificacion();
        if ([] !== $preferidos) {
            return $preferidos;
        }

        $fallback = [];
        if ('' !== trim($cliente->telefono())) {
            $fallback[] = 'whatsapp';
        }
        if ('' !== trim($cliente->email())) {
            $fallback[] = 'email';
        }

        return $fallback;
    }

    /**
     * @param list<string> $canales
     */
    public static function formatearCanales(array $canales): string
    {
        return implode(', ', array_map(
            static fn (string $c) => match ($c) {
                'whatsapp' => 'WhatsApp',
                'email' => 'email',
                default => $c,
            },
            $canales,
        ));
    }
}
