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
                . "Estado actual: pendiente de tramitación por parte de la oficina de extranjería.\n"
                . "Le avisaremos también cuando se asigne el número de expediente de extranjería "
                . "para que pueda consultar el estado en la sede electrónica y por SMS.\n\n"
                . "Consulte el detalle en su portal:\n%s",
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
        bool $esActualizacion = false,
    ): bool {
        $asunto = $esActualizacion
            ? sprintf('Seguimiento actualizado — Expediente %s', $expediente->numero())
            : sprintf('Seguimiento disponible — Expediente %s', $expediente->numero());
        $intro = $esActualizacion
            ? sprintf(
                "Se ha actualizado el número de seguimiento de su solicitud del expediente %s.\n\n"
                . "Nuevo número de expediente de extranjería: %s\n\n",
                $expediente->numero(),
                $numeroExpedienteExtranjeria,
            )
            : sprintf(
                "Ya puede consultar el estado de su solicitud del expediente %s.\n\n"
                . "Número de expediente de extranjería: %s\n\n",
                $expediente->numero(),
                $numeroExpedienteExtranjeria,
            );

        return $this->enviar(
            $cliente,
            $asunto,
            $intro
            . "Cómo consultar:\n"
            . '1) Web: '.self::INFOEXT_URL."\n"
            . sprintf("2) SMS gratuito: envíe el texto «EXPE %s» al 651 714 610\n\n", $numeroExpedienteExtranjeria)
            . "Los datos que facilite la Administración tienen carácter meramente informativo.\n\n"
            . "Portal del expediente:\n".$this->accessUrl($expediente),
            $expediente->numero(),
            $esActualizacion ? 'seguimiento_actualizado' : 'seguimiento',
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

    public function notificarRequerimientoPresentado(
        Expediente $expediente,
        Cliente $cliente,
        string $requerimientoNombre,
    ): bool {
        return $this->enviar(
            $cliente,
            sprintf('Requerimiento presentado — Expediente %s', $expediente->numero()),
            sprintf(
                "Su abogado ha presentado ante la Administración el requerimiento «%s» "
                . "del expediente %s.\n\n"
                . "Puede consultar el detalle en su portal:\n%s",
                $requerimientoNombre,
                $expediente->numero(),
                $this->accessUrl($expediente),
            ),
            $expediente->numero(),
            'requerimiento_presentado',
        );
    }

    public function notificarAvanceResolucion(Expediente $expediente, Cliente $cliente): bool
    {
        return $this->enviar(
            $cliente,
            sprintf('Tramitación completada — Expediente %s', $expediente->numero()),
            sprintf(
                "La tramitación del expediente %s ha finalizado.\n"
                . "Su expediente pasa ahora a la fase de resolución: su abogado aguardará "
                . "la decisión de la Administración y le avisará cuando haya novedades.\n\n"
                . "Portal:\n%s",
                $expediente->numero(),
                $this->accessUrl($expediente),
            ),
            $expediente->numero(),
            'avance_resolucion',
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
