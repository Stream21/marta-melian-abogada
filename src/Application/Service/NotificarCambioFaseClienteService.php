<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use Psr\Log\LoggerInterface;

/**
 * Aviso automático al cliente cuando el expediente cambia de fase de negocio.
 * Respeta la preferencia de canales del expediente (WhatsApp/email).
 */
final class NotificarCambioFaseClienteService
{
    public function __construct(
        private DespacharNotificacionClienteService $despachar,
        private ClienteRepositoryInterface $clienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private LoggerInterface $logger,
        private string $frontendBaseUrl,
    ) {
    }

    public function notificar(Expediente $expediente, FaseNegocioExpediente $faseDestino): void
    {
        if (FaseNegocioExpediente::Resolucion === $faseDestino) {
            return;
        }

        $contenido = $this->contenido($expediente, $faseDestino);
        if (null === $contenido) {
            return;
        }

        $clienteId = $expediente->clienteId();
        if (null === $clienteId || '' === $clienteId) {
            return;
        }

        $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
        if (null === $cliente) {
            $this->logger->warning('No se notificó el cambio de fase: cliente no encontrado.', [
                'expediente' => $expediente->numero(),
                'fase' => $faseDestino->value,
            ]);

            return;
        }

        $this->despachar->despachar(
            $expediente,
            'cambio_fase_' . $faseDestino->value,
            $contenido['asunto'],
            $contenido['mensaje'],
            'notificacion_cambio_fase',
            sprintf(
                'Se ha notificado al cliente el paso a fase %s por %%s.',
                $faseDestino->label(),
            ),
        );
    }

    /**
     * @return array{asunto: string, mensaje: string}|null
     */
    private function contenido(Expediente $expediente, FaseNegocioExpediente $faseDestino): ?array
    {
        $portal = $this->accessUrl($expediente);
        $numero = $expediente->numero();

        return match ($faseDestino) {
            FaseNegocioExpediente::Documentacion => [
                'asunto' => sprintf('Fase de documentación — Expediente %s', $numero),
                'mensaje' => $this->mensajeDocumentacion($expediente, $numero, $portal),
            ],
            FaseNegocioExpediente::Tramitacion => [
                'asunto' => sprintf('Fase de tramitación — Expediente %s', $numero),
                'mensaje' => sprintf(
                    "Marta Melián Abogados. Su expediente %s ha pasado a la fase de Tramitación.\n"
                    . "Su abogado se ocupará de presentar la solicitud ante la Administración y le avisaremos de cada novedad.\n"
                    . "Portal:\n%s",
                    $numero,
                    $portal,
                ),
            ],
            FaseNegocioExpediente::Resolucion,
            FaseNegocioExpediente::Contratacion => null,
        };
    }

    private function mensajeDocumentacion(Expediente $expediente, string $numero, string $portal): string
    {
        $docs = $this->documentoRequeridoRepository->findByExpediente($expediente->id());
        $nombres = [];
        foreach ($docs as $doc) {
            $nombres[] = $doc->nombre();
        }

        if ([] !== $nombres) {
            $lista = '';
            $letra = 'a';
            foreach ($nombres as $nombre) {
                $lista .= sprintf("%s) %s\n", $letra, $nombre);
                $letra = chr(ord($letra) + 1);
            }

            return sprintf(
                "Marta Melián Abogados. Su expediente %s ha pasado a la fase de Documentación.\n"
                . "Debe cumplimentar / aportar los siguientes documentos:\n%s\n"
                . "Acceda a su portal:\n%s",
                $numero,
                $lista,
                $portal,
            );
        }

        return sprintf(
            "Marta Melián Abogados. Su expediente %s ha pasado a la fase de Documentación.\n"
            . "En su portal encontrará los documentos que debe aportar:\n%s",
            $numero,
            $portal,
        );
    }

    private function accessUrl(Expediente $expediente): string
    {
        $token = $expediente->accessToken();
        if (null === $token || '' === $token) {
            return rtrim($this->frontendBaseUrl, '/');
        }

        return rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $token;
    }
}
