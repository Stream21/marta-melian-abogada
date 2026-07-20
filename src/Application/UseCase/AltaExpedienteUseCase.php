<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\AltaExpedienteInput;
use App\Application\DTO\AltaExpedienteResult;
use App\Application\DTO\ClienteInput;
use App\Application\DTO\ExpedienteResponse;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\NotificarAltaExpedienteService;
use App\Application\Service\TelefonoNormalizer;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteId;

final class AltaExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private GuardarClienteUseCase $guardarCliente,
        private ExpedienteFileStoragePort $fileStorage,
        private NotificarAltaExpedienteService $notificarAlta,
        private InicializarContratacionUseCase $inicializarContratacion,
        private ContratacionRepositoryInterface $contratacionRepository,
        private TelefonoNormalizer $telefonoNormalizer,
        private string $frontendBaseUrl,
    ) {
    }

    public function __invoke(AltaExpedienteInput $input): AltaExpedienteResult
    {
        $tramite = $this->tramiteRepository->findById(new TramiteId($input->tramiteId));
        if (null === $tramite) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }
        if (!$tramite->activo()) {
            throw new \InvalidArgumentException('El trámite seleccionado no está activo.');
        }

        if ($input->honorariosAcordados <= 0) {
            throw new \InvalidArgumentException('Los honorarios acordados deben ser mayores que cero.');
        }

        $metodoPago = MetodoPagoExpediente::tryFrom($input->metodoPago)
            ?? throw new \InvalidArgumentException('Método de pago no válido.');
        $planPago = PlanPagoExpediente::tryFrom($input->planPago)
            ?? throw new \InvalidArgumentException('Plan de pago no válido.');

        $numCuotas = $planPago === PlanPagoExpediente::Unico ? 1 : $input->numCuotas;
        if ($numCuotas < 1 || $numCuotas > 4) {
            throw new \InvalidArgumentException('El número de cuotas debe estar entre 1 y 4.');
        }

        $cliente = $this->resolverCliente($input);

        $expedienteId = ExpedienteId::generate();
        $numero = $this->expedienteRepository->nextNumeroForYear((int) date('Y'));
        $accessToken = bin2hex(random_bytes(32));
        $folderPath = 'var/expedientes/' . $expedienteId->value() . '/';
        $this->fileStorage->getFolderPath($expedienteId);

        $expediente = Expediente::crearAlta(
            $expedienteId,
            $numero,
            $tramite->nombre(),
            $cliente->nombre(),
            $folderPath,
            $cliente->id()->value(),
            $tramite->id()->value(),
            $tramite->servicioId()->value(),
            $input->honorariosAcordados,
            $metodoPago,
            $planPago,
            $numCuotas,
            $accessToken,
            $this->parseFechaVencimiento($input->fechaVencimientoFase),
        );

        $this->expedienteRepository->save($expediente);
        ($this->inicializarContratacion)($expedienteId);

        $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $accessToken;
        $canales = [];
        if ($input->notificar) {
            $canalesSolicitados = $this->resolverCanalesNotificacion($input, $cliente);
            $canales = $this->notificarAlta->notificar($expediente, $cliente, $tramite->nombre(), $canalesSolicitados);
            if ([] !== $canales) {
                $this->contratacionRepository->saveHito(new ExpedienteHito(
                    bin2hex(random_bytes(16)),
                    $expedienteId,
                    'notificacion_alta_expediente',
                    sprintf(
                        'Se ha notificado al cliente el alta del expediente por %s.',
                        implode(', ', array_map(
                            fn (string $c) => match ($c) {
                                'whatsapp' => 'WhatsApp',
                                'email' => 'email',
                                default => $c,
                            },
                            array_filter($canales, fn (string $c) => !str_ends_with($c, '_error')),
                        )),
                    ),
                    ActorHitoExpediente::Abogado,
                    new \DateTimeImmutable('now'),
                ));
            }
        }

        return new AltaExpedienteResult(
            $this->toResponse($expediente, $accessUrl),
            $accessUrl,
            $canales,
        );
    }

    /**
     * @return string[]
     */
    private function resolverCanalesNotificacion(AltaExpedienteInput $input, \App\Domain\Entity\Cliente $cliente): array
    {
        $solicitados = array_values(array_unique(array_filter(
            $input->canalesNotificacion,
            fn (string $c) => in_array($c, ['whatsapp', 'email'], true),
        )));

        if ([] === $solicitados) {
            throw new \InvalidArgumentException('Debe seleccionar al menos un canal de notificación (WhatsApp o email).');
        }

        $telefono = trim($cliente->telefono());
        $email = trim($cliente->email());

        if (in_array('whatsapp', $solicitados, true) && '' === $telefono) {
            throw new \InvalidArgumentException('No se puede notificar por WhatsApp sin teléfono del cliente.');
        }

        if (in_array('email', $solicitados, true) && '' === $email) {
            throw new \InvalidArgumentException('No se puede notificar por email sin correo del cliente.');
        }

        return $solicitados;
    }

    private function parseFechaVencimiento(?string $fecha): ?\DateTimeImmutable
    {
        if (null === $fecha || '' === trim($fecha)) {
            return (new \DateTimeImmutable('now'))->modify('+1 month')->setTime(23, 59, 59);
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', trim($fecha));

        return $parsed
            ? $parsed->setTime(23, 59, 59)
            : throw new \InvalidArgumentException('Fecha de vencimiento no válida.');
    }

    private function resolverCliente(AltaExpedienteInput $input): \App\Domain\Entity\Cliente
    {
        if (null !== $input->clienteId && '' !== $input->clienteId) {
            $cliente = $this->clienteRepository->findById(new ClienteId($input->clienteId));
            if (null === $cliente) {
                throw new \InvalidArgumentException('Cliente no encontrado.');
            }

            $telefono = $this->telefonoNormalizer->normalize($cliente->telefono());
            if (null === $telefono || !$this->telefonoNormalizer->isValid($telefono)) {
                throw new \InvalidArgumentException('El cliente debe tener un teléfono móvil válido.');
            }

            return $cliente;
        }

        $telefono = $this->telefonoNormalizer->normalize($input->telefono);
        if (null === $telefono || !$this->telefonoNormalizer->isValid($telefono)) {
            throw new \InvalidArgumentException('El teléfono del cliente es obligatorio y debe ser válido.');
        }

        return ($this->guardarCliente)(null, new ClienteInput(
            nombre: '',
            telefono: $telefono,
            email: $input->email ?? '',
        ), altaMinima: true);
    }

    private function toResponse(Expediente $expediente, string $accessUrl): ExpedienteResponse
    {
        return new ExpedienteResponse(
            id: $expediente->id()->value(),
            numero: $expediente->numero(),
            titulo: $expediente->titulo(),
            estado: $expediente->estado()->value,
            fechaApertura: $expediente->fechaApertura()->format(\DateTimeInterface::ATOM),
            clientName: $expediente->clientName(),
            caseReference: $expediente->caseReference(),
            folderPath: $expediente->folderPath(),
            paymentStatus: $expediente->paymentStatus(),
            clienteId: $expediente->clienteId(),
            tramiteId: $expediente->tramiteId(),
            servicioId: $expediente->servicioId(),
            faseNegocio: $expediente->faseNegocio()->value,
            estadoFase: $expediente->estadoFase()->value,
            honorariosAcordados: $expediente->honorariosAcordados(),
            metodoPago: $expediente->metodoPago()->value,
            planPago: $expediente->planPago()->value,
            numCuotas: $expediente->numCuotas(),
            accessUrl: $accessUrl,
        );
    }
}
