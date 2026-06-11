<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\EstadoExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteRepository implements ExpedienteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Expediente $expediente): void
    {
        $existing = $this->entityManager->getRepository(ExpedienteOrm::class)->find($expediente->id()->value());

        if ($existing instanceof ExpedienteOrm) {
            $this->applyDomainToOrm($existing, $expediente);
        } else {
            $this->entityManager->persist($this->domainToOrm($expediente));
        }

        $this->entityManager->flush();
    }

    public function findById(ExpedienteId $id): ?Expediente
    {
        $orm = $this->entityManager->getRepository(ExpedienteOrm::class)->find($id->value());

        return $orm instanceof ExpedienteOrm ? $this->ormToDomain($orm) : null;
    }

    public function findByAccessToken(string $token): ?Expediente
    {
        $orm = $this->entityManager->getRepository(ExpedienteOrm::class)->findOneBy(['accessToken' => $token]);

        return $orm instanceof ExpedienteOrm ? $this->ormToDomain($orm) : null;
    }

    public function nextNumeroForYear(int $year): string
    {
        $prefix = 'EXP-' . $year . '/';
        $conn = $this->entityManager->getConnection();
        $result = $conn->fetchOne(
            'SELECT numero FROM expediente WHERE numero LIKE :prefix ORDER BY numero DESC LIMIT 1',
            ['prefix' => $prefix . '%'],
        );

        $secuencia = 1;
        if (is_string($result) && preg_match('/EXP-\d{4}\/(\d+)$/', $result, $matches)) {
            $secuencia = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $secuencia, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return Expediente[]
     */
    public function findAll(): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteOrm::class)->findBy([], ['fechaApertura' => 'DESC']);

        return array_map($this->ormToDomain(...), $orms);
    }

    public function findByClienteId(ClienteId $clienteId): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteOrm::class)->findBy(
            ['clienteId' => $clienteId->value()],
            ['fechaApertura' => 'DESC'],
        );

        return array_map($this->ormToDomain(...), $orms);
    }

    public function remove(Expediente $expediente): void
    {
        $orm = $this->entityManager->getRepository(ExpedienteOrm::class)->find($expediente->id()->value());
        if ($orm instanceof ExpedienteOrm) {
            $this->entityManager->remove($orm);
            $this->entityManager->flush();
        }
    }

    private function ormToDomain(ExpedienteOrm $orm): Expediente
    {
        return new Expediente(
            new ExpedienteId($orm->getId()),
            $orm->getNumero(),
            $orm->getTitulo(),
            EstadoExpediente::from($orm->getEstado()),
            $orm->getFechaApertura(),
            $orm->getClientName(),
            $orm->getCaseReference(),
            $orm->getFolderPath(),
            $orm->getPaymentStatus(),
            $orm->getClienteId(),
            $orm->getTramiteId(),
            $orm->getServicioId(),
            FaseNegocioExpediente::from($orm->getFaseNegocio()),
            EstadoFaseExpediente::from($orm->getEstadoFase()),
            (float) $orm->getHonorariosAcordados(),
            MetodoPagoExpediente::from($orm->getMetodoPago()),
            PlanPagoExpediente::from($orm->getPlanPago()),
            $orm->getNumCuotas(),
            $orm->getAccessToken(),
        );
    }

    private function domainToOrm(Expediente $expediente): ExpedienteOrm
    {
        $orm = new ExpedienteOrm();
        $orm->setId($expediente->id()->value());
        $this->applyDomainToOrm($orm, $expediente);

        return $orm;
    }

    private function applyDomainToOrm(ExpedienteOrm $orm, Expediente $expediente): void
    {
        $orm->setNumero($expediente->numero());
        $orm->setTitulo($expediente->titulo());
        $orm->setEstado($expediente->estado()->value);
        $orm->setFechaApertura($expediente->fechaApertura());
        $orm->setClientName($expediente->clientName());
        $orm->setCaseReference($expediente->caseReference());
        $orm->setFolderPath($expediente->folderPath());
        $orm->setPaymentStatus($expediente->paymentStatus());
        $orm->setClienteId($expediente->clienteId());
        $orm->setTramiteId($expediente->tramiteId());
        $orm->setServicioId($expediente->servicioId());
        $orm->setFaseNegocio($expediente->faseNegocio()->value);
        $orm->setEstadoFase($expediente->estadoFase()->value);
        $orm->setHonorariosAcordados(number_format($expediente->honorariosAcordados(), 2, '.', ''));
        $orm->setMetodoPago($expediente->metodoPago()->value);
        $orm->setPlanPago($expediente->planPago()->value);
        $orm->setNumCuotas($expediente->numCuotas());
        $orm->setAccessToken($expediente->accessToken());
    }
}
