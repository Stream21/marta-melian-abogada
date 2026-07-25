<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;
use App\Application\Port\EmailPort;
use Psr\Log\LoggerInterface;

final class NotificarTramitacionClienteService
{
    private const INFOEXT_URL = 'https://sede.administracionespublicas.gob.es/pagina/index/directorio/infoext2';

    public function __construct(
        private EmailPort $emailPort,
        private LoggerInterface $logger,
        private string $frontendBaseUrl,
    ) {
    }

    public function notificarPresentacionRegistrada(Expediente $expediente, Cliente $cliente): bool
    {
        return $this->enviar(
            $cliente,
            sprintf('Solicitud presentada — Expediente %s', $expediente->numero()),
            sprintf(
                "Su solicitud del expediente %s ha sido presentada ante la Administración.\n\n"
                . "Estado actual: pendiente de tramitación por parte de la oficina de extranjería.\n\n"
                . "Cuando exista el número de expediente de extranjería, podrá consultar el estado en la sede electrónica y por SMS desde su portal:\n%s",
                $expediente->numero(),
                $this->accessUrl($expediente),
            ),
            $expediente->numero(),
            'presentacion',
        );
    }

    public function notificarSeguimientoAsignado(
        Expediente $expediente,
        Cliente $cliente,
        string $numeroExpedienteExtranjeria,
    ): bool {
        return $this->enviar(
            $cliente,
            sprintf('Seguimiento disponible — Expediente %s', $expediente->numero()),
            sprintf(
                "Ya puede consultar el estado de su solicitud del expediente %s.\n\n"
                . "Número de expediente de extranjería: %s\n\n"
                . "Cómo consultar:\n"
                . "1) Web: %s\n"
                . "2) SMS gratuito: envíe el texto «EXPE %s» al 651 714 610\n\n"
                . "Los datos que facilite la Administración tienen carácter meramente informativo.\n\n"
                . "Portal del expediente:\n%s",
                $expediente->numero(),
                $numeroExpedienteExtranjeria,
                self::INFOEXT_URL,
                $numeroExpedienteExtranjeria,
                $this->accessUrl($expediente),
            ),
            $expediente->numero(),
            'seguimiento',
        );
    }

    public function notificarRequerimientoCliente(
        Expediente $expediente,
        Cliente $cliente,
        string $documentoNombre,
        string $nota = '',
    ): bool {
        $cuerpoNota = '' !== trim($nota)
            ? sprintf("\n\nMensaje de su abogado:\n%s", trim($nota))
            : '';

        return $this->enviar(
            $cliente,
            sprintf('Acción requerida — Expediente %s', $expediente->numero()),
            sprintf(
                "Su abogado le ha solicitado completar «%s» para continuar la tramitación del expediente %s.%s\n\n"
                . "Acceda a su portal:\n%s",
                $documentoNombre,
                $expediente->numero(),
                $cuerpoNota,
                $this->accessUrl($expediente),
            ),
            $expediente->numero(),
            'requerimiento',
        );
    }

    public function notificarVueltaSeguimiento(Expediente $expediente, Cliente $cliente): bool
    {
        return $this->enviar(
            $cliente,
            sprintf('Tramitación actualizada — Expediente %s', $expediente->numero()),
            sprintf(
                "El expediente %s vuelve a estar en seguimiento ante la Administración.\n"
                . "No se requiere ninguna acción por su parte en este momento.\n\n"
                . "Portal:\n%s",
                $expediente->numero(),
                $this->accessUrl($expediente),
            ),
            $expediente->numero(),
            'vuelta_seguimiento',
        );
    }

    private function accessUrl(Expediente $expediente): string
    {
        return rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
    }

    private function enviar(
        Cliente $cliente,
        string $asunto,
        string $mensaje,
        string $expedienteNumero,
        string $contexto,
    ): bool {
        $email = trim($cliente->email());
        if ('' === $email) {
            $this->logger->warning('No se pudo notificar tramitación al cliente: sin correo.', [
                'expediente' => $expedienteNumero,
                'contexto' => $contexto,
            ]);

            return false;
        }

        try {
            $this->emailPort->send($email, $asunto, $mensaje);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Error enviando notificación de tramitación', [
                'email' => $email,
                'expediente' => $expedienteNumero,
                'contexto' => $contexto,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
