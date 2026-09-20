<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\DespacharNotificacionClienteService;
use App\Domain\Entity\EstadoExpediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\NotificacionVencimientoEnviada;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\NotificacionVencimientoEnviadaRepositoryInterface;

final class VerificarVencimientosExpedienteUseCase
{
    /** Días restantes hasta el vencimiento (positivo = futuro). @var list<int> */
    private const DIAS_ALERTA = [7, 3, 0];

    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private NotificacionVencimientoEnviadaRepositoryInterface $enviadaRepository,
        private DespacharNotificacionClienteService $despachar,
        private string $frontendBaseUrl,
    ) {
    }

    public function __invoke(?\DateTimeImmutable $hoy = null): int
    {
        $hoy ??= new \DateTimeImmutable('today');
        $alertas = 0;

        foreach ($this->expedienteRepository->findAll() as $expediente) {
            if ($expediente->estado() !== EstadoExpediente::Abierto) {
                continue;
            }

            if ($expediente->faseNegocio() === FaseNegocioExpediente::Resolucion) {
                continue;
            }

            $vencimiento = $expediente->fechaVencimientoFase();
            if (null === $vencimiento) {
                continue;
            }

            $vencimientoDia = $vencimiento->setTime(0, 0);
            $dias = (int) $hoy->diff($vencimientoDia)->format('%r%a');
            if (!in_array($dias, self::DIAS_ALERTA, true)) {
                continue;
            }

            if ($this->enviadaRepository->exists($expediente->id(), $vencimientoDia, $dias)) {
                continue;
            }

            $portal = $this->accessUrl($expediente);
            $textoDias = match ($dias) {
                0 => 'hoy',
                3 => 'en 3 días',
                7 => 'en 1 semana',
                default => sprintf('en %d día(s)', abs($dias)),
            };

            $asunto = sprintf('Vencimiento de plazo — Expediente %s', $expediente->numero());
            $mensaje = sprintf(
                "Marta Melián Abogados. El plazo de su expediente %s vence %s (%s).\n\n"
                . "Acceda a su portal para completar lo pendiente:\n%s",
                $expediente->numero(),
                $textoDias,
                $vencimientoDia->format('d/m/Y'),
                $portal,
            );

            $this->despachar->despachar(
                $expediente,
                'vencimiento',
                $asunto,
                $mensaje,
                'notificacion_vencimiento',
                sprintf(
                    'Se ha notificado al cliente el vencimiento (día %d) por %%s.',
                    $dias,
                ),
            );

            $this->enviadaRepository->save(new NotificacionVencimientoEnviada(
                bin2hex(random_bytes(16)),
                $expediente->id(),
                $vencimientoDia,
                $dias,
                new \DateTimeImmutable('now'),
            ));

            ++$alertas;
        }

        return $alertas;
    }

    private function accessUrl(\App\Domain\Entity\Expediente $expediente): string
    {
        $token = $expediente->accessToken();
        if (null === $token || '' === $token) {
            return rtrim($this->frontendBaseUrl, '/');
        }

        return rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $token;
    }
}
