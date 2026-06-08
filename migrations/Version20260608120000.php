<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace tipo_caso with servicio and tramite tables (logical delete via activo)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS tipo_caso');

        $this->addSql('CREATE TABLE servicio (
            id VARCHAR(36) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            activo BOOLEAN NOT NULL DEFAULT true,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE UNIQUE INDEX uniq_servicio_nombre_lower ON servicio (LOWER(TRIM(nombre)))');

        $this->addSql('CREATE TABLE tramite (
            id VARCHAR(36) NOT NULL,
            servicio_id VARCHAR(36) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            activo BOOLEAN NOT NULL DEFAULT true,
            PRIMARY KEY(id),
            CONSTRAINT fk_tramite_servicio FOREIGN KEY (servicio_id) REFERENCES servicio (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');

        $this->addSql('CREATE INDEX idx_tramite_servicio_id ON tramite (servicio_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_tramite_servicio_nombre_lower ON tramite (servicio_id, LOWER(TRIM(nombre)))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS tramite');
        $this->addSql('DROP TABLE IF EXISTS servicio');

        $this->addSql('CREATE TABLE tipo_caso (
            id VARCHAR(36) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_tipo_caso_nombre_lower ON tipo_caso (LOWER(TRIM(nombre)))');
    }
}
