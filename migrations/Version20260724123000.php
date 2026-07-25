<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fase 3 tramitación: subfase, presentación telemática y requerimientos Mercurio.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente ADD COLUMN IF NOT EXISTS subfase_tramitacion VARCHAR(40) DEFAULT NULL');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS expediente_presentacion_telematica (
                id VARCHAR(36) NOT NULL,
                expediente_id VARCHAR(36) NOT NULL,
                presentacion_path VARCHAR(500) NOT NULL,
                justificante_path VARCHAR(500) NOT NULL,
                identificador_solicitud VARCHAR(100) NOT NULL,
                fecha_presentacion TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                numero_expediente_extranjeria VARCHAR(15) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_presentacion_expediente ON expediente_presentacion_telematica (expediente_id)');
        $this->addSql("COMMENT ON COLUMN expediente_presentacion_telematica.fecha_presentacion IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN expediente_presentacion_telematica.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN expediente_presentacion_telematica.updated_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS expediente_requerimiento_mercurio (
                id VARCHAR(36) NOT NULL,
                expediente_id VARCHAR(36) NOT NULL,
                tipo VARCHAR(20) NOT NULL,
                destino VARCHAR(20) NOT NULL,
                nombre VARCHAR(255) NOT NULL,
                descripcion TEXT NOT NULL,
                estado VARCHAR(30) NOT NULL,
                archivo_path VARCHAR(500) DEFAULT NULL,
                archivo_nombre VARCHAR(255) DEFAULT NULL,
                justificante_presentacion_path VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_req_mercurio_expediente ON expediente_requerimiento_mercurio (expediente_id)');
        $this->addSql("COMMENT ON COLUMN expediente_requerimiento_mercurio.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN expediente_requerimiento_mercurio.updated_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS expediente_requerimiento_mercurio');
        $this->addSql('DROP TABLE IF EXISTS expediente_presentacion_telematica');
        $this->addSql('ALTER TABLE expediente DROP COLUMN IF EXISTS subfase_tramitacion');
    }
}
