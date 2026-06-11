<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ClienteResponseMapper;
use App\Application\DTO\ExpedienteResponseMapper;
use App\Application\Service\ClienteEdicionPolicy;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;

final class ObtenerClienteDetalleUseCase
{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private string $frontendBaseUrl,
        private ClienteEdicionPolicy $edicionPolicy,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $clienteId): array
    {
        $id = new ClienteId($clienteId);
        $cliente = $this->clienteRepository->findById($id);
        if (null === $cliente) {
            throw new \InvalidArgumentException('Cliente no encontrado.');
        }

        $expedientes = $this->expedienteRepository->findByClienteId($id);

        $clientePayload = ClienteResponseMapper::fromDomain($cliente, count($expedientes));
        if ($cliente->tieneDocumentoIdentidad()) {
            $clientePayload['documentoIdentidad']['anversoUrl'] = '/api/clientes/' . $clienteId . '/documento-identidad/anverso';
            if (null !== $cliente->documentoIdentidadReversoPath()) {
                $clientePayload['documentoIdentidad']['reversoUrl'] = '/api/clientes/' . $clienteId . '/documento-identidad/reverso';
            }
        }

        return [
            'cliente' => $clientePayload,
            'edicionBloqueada' => !$this->edicionPolicy->puedeEditar($id),
            'motivoEdicionBloqueada' => $this->edicionPolicy->motivoBloqueo($id),
            'expedientesAbiertos' => $this->edicionPolicy->expedientesConProcesoAbierto($id),
            'expedientes' => array_map(function ($e) {
                $resp = ExpedienteResponseMapper::fromDomain($e, $this->frontendBaseUrl);

                return [
                    'id' => $resp->id,
                    'numero' => $resp->numero,
                    'titulo' => $resp->titulo,
                    'estado' => $resp->estado,
                    'fechaApertura' => $resp->fechaApertura,
                    'clientName' => $resp->clientName,
                    'faseNegocio' => $resp->faseNegocio,
                    'faseNegocioLabel' => $e->faseNegocio()->label(),
                    'estadoFase' => $resp->estadoFase,
                    'estadoFaseLabel' => $e->estadoFase()->label(),
                    'honorariosAcordados' => $resp->honorariosAcordados,
                    'paymentStatus' => $resp->paymentStatus,
                    'tramiteId' => $resp->tramiteId,
                ];
            }, $expedientes),
            'tramitesDerivadosPendientes' => [],
        ];
    }
}
