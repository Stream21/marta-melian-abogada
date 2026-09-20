<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\AltaExpedienteInput;
use App\Application\DTO\AltaExpedienteResult;
use App\Application\DTO\ClienteInput;
use App\Application\DTO\ExpedienteResponseMapper;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\NotificarAltaExpedienteService;
use App\Application\Service\TelefonoNormalizer;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\Repository\ClienteRepositoryInterface;
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

        // En expediente: nunca mostrar «Cliente pendiente». Cliente nuevo → teléfono de contacto;
        // cliente ya registrado → su nombre real (2.º servicio u otro trámite).
        $nombreEnExpediente = $cliente->esProvisional()
            ? $cliente->telefono()
            : $cliente->nombre();

        $expedienteId = ExpedienteId::generate();
        $numero = $this->expedienteRepository->nextNumeroForYear((int) date('Y'));
        $accessToken = bin2hex(random_bytes(32));
        $folderPath = 'var/expedientes/' . $expedienteId->value() . '/';
        $this->fileStorage->getFolderPath($expedienteId);

        $canalesPreferidos = Expediente::normalizarCanales($input->canalesNotificacion);
        if ($input->notificar) {
            $canalesPreferidos = $this->resolverCanalesNotificacion($input, $cliente);
        }

        $expediente = Expediente::crearAlta(
            $expedienteId,
            $numero,
            $tramite->nombre(),
            $nombreEnExpediente,
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
            $canalesPreferidos,
        );

        $this->expedienteRepository->save($expediente);
        ($this->inicializarContratacion)($expedienteId);

        $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $accessToken;
        $canales = [];
        if ($input->notificar) {
            $canales = $this->notificarAlta->notificar($expediente, $cliente, $tramite->nombre(), $canalesPreferidos);
        }

        return new AltaExpedienteResult(
            ExpedienteResponseMapper::fromDomain($expediente, $this->frontendBaseUrl),
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
            // Fase 1 (contratación): plazo por defecto de 2 semanas desde la creación.
            return (new \DateTimeImmutable('now'))->modify('+2 weeks')->setTime(23, 59, 59);
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
        ), altaMinima: true, permitirDuplicado: $input->permitirDuplicado);
    }
}
