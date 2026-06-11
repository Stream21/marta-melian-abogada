<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\DespachoConfig;
use App\Domain\Repository\DespachoConfigRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\DespachoConfigOrm;
use Doctrine\ORM\EntityManagerInterface;

final class DespachoConfigRepository implements DespachoConfigRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function find(): ?DespachoConfig
    {
        $orm = $this->entityManager->getRepository(DespachoConfigOrm::class)->find(DespachoConfig::DEFAULT_ID);

        return $orm instanceof DespachoConfigOrm ? $this->ormToDomain($orm) : null;
    }

    public function save(DespachoConfig $config): void
    {
        $existing = $this->entityManager->getRepository(DespachoConfigOrm::class)->find($config->id());
        $now = new \DateTimeImmutable();

        if ($existing instanceof DespachoConfigOrm) {
            $this->applyDomainToOrm($existing, $config, $now);
        } else {
            $orm = new DespachoConfigOrm();
            $orm->setId($config->id());
            $this->applyDomainToOrm($orm, $config, $now);
            $this->entityManager->persist($orm);
        }

        $this->entityManager->flush();
    }

    private function applyDomainToOrm(DespachoConfigOrm $orm, DespachoConfig $config, \DateTimeImmutable $now): void
    {
        $orm->setNombreFirma($config->nombreFirma());
        $orm->setNombreLetrada($config->nombreLetrada());
        $orm->setNumColegiado($config->numColegiado());
        $orm->setDireccion($config->direccion());
        $orm->setCiudad($config->ciudad());
        $orm->setSubtituloProfesional($config->subtituloProfesional());
        $orm->setTelefono($config->telefono());
        $orm->setEmail($config->email());
        $orm->setWeb($config->web());
        $orm->setNif($config->nif());
        $orm->setColegioAbogados($config->colegioAbogados());
        $orm->setIban($config->iban());
        $orm->setEntidadBancaria($config->entidadBancaria());
        $orm->setTitularCuenta($config->titularCuenta());
        $orm->setCabeceraHtml($config->cabeceraHtml());
        $orm->setPieHtml($config->pieHtml());
        $orm->setLogoPath($config->logoPath());
        $orm->setSelloPath($config->selloPath());
        $orm->setUpdatedAt($now);
    }

    private function ormToDomain(DespachoConfigOrm $orm): DespachoConfig
    {
        return new DespachoConfig(
            $orm->getId(),
            $orm->getNombreFirma(),
            $orm->getNombreLetrada(),
            $orm->getNumColegiado(),
            $orm->getDireccion(),
            $orm->getCiudad(),
            $orm->getSubtituloProfesional(),
            $orm->getTelefono(),
            $orm->getEmail(),
            $orm->getWeb(),
            $orm->getNif(),
            $orm->getColegioAbogados(),
            $orm->getIban(),
            $orm->getEntidadBancaria(),
            $orm->getTitularCuenta(),
            $orm->getCabeceraHtml(),
            $orm->getPieHtml(),
            $orm->getLogoPath(),
            $orm->getSelloPath(),
            $orm->getUpdatedAt(),
        );
    }
}
