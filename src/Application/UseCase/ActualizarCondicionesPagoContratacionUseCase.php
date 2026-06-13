<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\CalendarioPagoService;
use App\Application\Service\ContratacionCompletitudValidator;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteFirmaRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ActualizarCondicionesPagoContratacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteFirmaRepositoryInterface $firmaRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionCompletitudValidator $completitudValidator,
        private CalendarioPagoService $calendarioPagoService,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        string $expedienteId,
        string $metodoPago,
        string $planPago,
        int $numCuotas,
        ?float $honorariosAcordados = null,
    ): array {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('Solo se pueden modificar condiciones de pago en fase de contratación.');
        }

        if (!$this->puedeEditarCondicionesPago($id)) {
            throw new \InvalidArgumentException(
                'Las condiciones de pago ya no se pueden modificar: el cliente ha comenzado a firmar documentos.',
            );
        }

        $metodo = MetodoPagoExpediente::tryFrom($metodoPago)
            ?? throw new \InvalidArgumentException('Método de pago no válido.');
        $plan = PlanPagoExpediente::tryFrom($planPago)
            ?? throw new \InvalidArgumentException('Plan de pago no válido.');

        $cuotas = $plan === PlanPagoExpediente::Unico ? 1 : max(2, min(4, $numCuotas));
        $honorarios = null !== $honorariosAcordados ? $honorariosAcordados : $expediente->honorariosAcordados();
        if ($honorarios <= 0) {
            throw new \InvalidArgumentException('Los honorarios deben ser mayores que cero.');
        }

        $expedienteActualizado = $expediente
            ->withCondicionesPago($honorarios, $metodo, $plan, $cuotas)
            ->withCalendarioPagos(null)
            ->withFechaFirmaContrato(null);

        $this->expedienteRepository->save($expedienteActualizado);

        $proyectado = $this->calendarioPagoService->calcular(
            $honorarios,
            $plan,
            $cuotas,
            new \DateTimeImmutable('today'),
        );

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'condiciones_pago_actualizadas',
            sprintf(
                'Condiciones de pago actualizadas: %s, %s (%d cuota(s), %s €).',
                $metodo->label(),
                $plan->label(),
                $cuotas,
                number_format($honorarios, 2, ',', '.'),
            ),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
            PasoContratacionCliente::Firmas,
        ));

        $this->realtime->publishContratacionUpdate($expedienteId, [
            'type' => 'condiciones_pago_actualizadas',
            'actor' => 'abogado',
        ]);

        return [
            'metodoPago' => $metodo->value,
            'metodoPagoLabel' => $metodo->label(),
            'planPago' => $plan->value,
            'planPagoLabel' => $plan->label(),
            'numCuotas' => $cuotas,
            'honorariosAcordados' => $honorarios,
            'calendarioProyectado' => $proyectado,
            'condicionesPagoEditables' => true,
        ];
    }

    public function puedeEditarCondicionesPago(ExpedienteId $expedienteId): bool
    {
        foreach ($this->firmaRepository->findByExpediente($expedienteId) as $firma) {
            return false;
        }

        return true;
    }
}
