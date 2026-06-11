<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260609120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add despacho_config and hoja_encargo_plantilla tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE despacho_config (
            id VARCHAR(36) NOT NULL,
            nombre_firma VARCHAR(255) NOT NULL,
            nombre_letrada VARCHAR(255) NOT NULL,
            num_colegiado VARCHAR(50) NOT NULL,
            direccion VARCHAR(255) NOT NULL,
            ciudad VARCHAR(100) NOT NULL,
            logo_path VARCHAR(255) DEFAULT NULL,
            sello_path VARCHAR(255) DEFAULT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE TABLE hoja_encargo_plantilla (
            id VARCHAR(36) NOT NULL,
            tramite_id VARCHAR(36) NOT NULL,
            bloques JSON NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_HE_PLANTILLA_TRAMITE ON hoja_encargo_plantilla (tramite_id)');
        $this->addSql('ALTER TABLE hoja_encargo_plantilla ADD CONSTRAINT FK_HE_PLANTILLA_TRAMITE FOREIGN KEY (tramite_id) REFERENCES tramite (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE hoja_encargo_plantilla DROP CONSTRAINT FK_HE_PLANTILLA_TRAMITE');
        $this->addSql('DROP TABLE hoja_encargo_plantilla');
        $this->addSql('DROP TABLE despacho_config');
    }
}
