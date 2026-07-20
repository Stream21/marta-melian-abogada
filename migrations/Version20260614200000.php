<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260614200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fase 2 requerimientos: expediente_documento_requerido, expediente_escrito, servicio_documento_requerido y ampliación de entregas';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS servicio_documento_requerido (
            id VARCHAR(36) NOT NULL,
            servicio_id VARCHAR(36) NOT NULL,
            fase INT DEFAULT 2 NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            obligatorio BOOLEAN NOT NULL,
            tipo VARCHAR(20) DEFAULT \'individual\' NOT NULL,
            max_imagenes INT DEFAULT 1 NOT NULL,
            formatos JSON NOT NULL,
            orden INT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_SERVICIO_DOC_REQ_SERVICIO ON servicio_documento_requerido (servicio_id)');
        $this->addSql('ALTER TABLE servicio_documento_requerido DROP CONSTRAINT IF EXISTS FK_SERVICIO_DOC_REQ_SERVICIO');
        $this->addSql('ALTER TABLE servicio_documento_requerido ADD CONSTRAINT FK_SERVICIO_DOC_REQ_SERVICIO FOREIGN KEY (servicio_id) REFERENCES servicio (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE IF NOT EXISTS expediente_documento_requerido (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            obligatorio BOOLEAN NOT NULL,
            tipo VARCHAR(20) DEFAULT \'individual\' NOT NULL,
            max_imagenes INT DEFAULT 1 NOT NULL,
            orden INT NOT NULL,
            origen VARCHAR(20) NOT NULL,
            tramite_documento_requerido_id VARCHAR(36) DEFAULT NULL,
            servicio_documento_requerido_id VARCHAR(36) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_EXP_DOC_REQ_EXPEDIENTE ON expediente_documento_requerido (expediente_id)');

        $this->addSql('CREATE TABLE IF NOT EXISTS expediente_escrito (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            contenido_html TEXT NOT NULL,
            pdf_path VARCHAR(500) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_EXP_ESCRITO_EXPEDIENTE ON expediente_escrito (expediente_id)');

        $this->addSql('ALTER TABLE expediente_documento_entregado ALTER COLUMN documento_requerido_id DROP NOT NULL');
        $this->addSql('ALTER TABLE expediente_documento_entregado ADD COLUMN IF NOT EXISTS expediente_documento_requerido_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE expediente_documento_entregado ADD COLUMN IF NOT EXISTS nota_rechazo TEXT DEFAULT NULL');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_EXP_DOC_ENTREGADO');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_EXP_DOC_ENT_TRAMITE ON expediente_documento_entregado (expediente_id, documento_requerido_id) WHERE documento_requerido_id IS NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_EXP_DOC_ENT_EXPEDIENTE ON expediente_documento_entregado (expediente_id, expediente_documento_requerido_id) WHERE expediente_documento_requerido_id IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UNIQ_EXP_DOC_ENT_EXPEDIENTE');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_EXP_DOC_ENT_TRAMITE');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_EXP_DOC_ENTREGADO ON expediente_documento_entregado (expediente_id, documento_requerido_id)');
        $this->addSql('ALTER TABLE expediente_documento_entregado DROP COLUMN IF EXISTS nota_rechazo');
        $this->addSql('ALTER TABLE expediente_documento_entregado DROP COLUMN IF EXISTS expediente_documento_requerido_id');

        $this->addSql('DROP TABLE IF EXISTS expediente_escrito');
        $this->addSql('DROP TABLE IF EXISTS expediente_documento_requerido');
        $this->addSql('ALTER TABLE servicio_documento_requerido DROP CONSTRAINT IF EXISTS FK_SERVICIO_DOC_REQ_SERVICIO');
        $this->addSql('DROP TABLE IF EXISTS servicio_documento_requerido');
    }
}
