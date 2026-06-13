<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Complementa Version20260611120000 (columnas expediente base) y Version20260611210000 (cliente ficha).
 * Solo añade campos/tablas nuevos de contratación Fase 1.
 */
final class Version20260613120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Contratación Fase 1: vencimientos, Holded/identidad cliente, pasos, hitos, documentos y firmas';
    }

    public function up(Schema $schema): void
    {
        // Columnas expediente no presentes en Version20260611120000
        $this->addSql('ALTER TABLE expediente ADD COLUMN IF NOT EXISTS fecha_vencimiento_fase TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE expediente ADD COLUMN IF NOT EXISTS fecha_ultimo_cambio_estado TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // Campos Holded e identidad en cliente
        $this->addSql('ALTER TABLE cliente ADD COLUMN IF NOT EXISTS holded_contact_id VARCHAR(64) DEFAULT NULL');
        $this->addSql("ALTER TABLE cliente ADD COLUMN IF NOT EXISTS holded_estado VARCHAR(20) DEFAULT 'oportunidad' NOT NULL");
        $this->addSql('ALTER TABLE cliente ADD COLUMN IF NOT EXISTS holded_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD COLUMN IF NOT EXISTS holded_sync_error VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD COLUMN IF NOT EXISTS documento_identidad_tipo VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD COLUMN IF NOT EXISTS documento_identidad_anverso_path VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD COLUMN IF NOT EXISTS documento_identidad_reverso_path VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD COLUMN IF NOT EXISTS documento_identidad_escaneado_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        $this->addSql('CREATE TABLE IF NOT EXISTS expediente_contratacion_paso (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            paso VARCHAR(30) NOT NULL,
            estado VARCHAR(30) DEFAULT \'pendiente\' NOT NULL,
            realizado_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            validado_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_CONTRATACION_EXPEDIENTE_PASO ON expediente_contratacion_paso (expediente_id, paso)');

        $this->addSql('CREATE TABLE IF NOT EXISTS expediente_hito (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            paso VARCHAR(30) DEFAULT NULL,
            tipo VARCHAR(50) NOT NULL,
            descripcion VARCHAR(500) NOT NULL,
            actor VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_EXPEDIENTE_HITO_EXPEDIENTE ON expediente_hito (expediente_id)');

        $this->addSql('CREATE TABLE IF NOT EXISTS expediente_documento_entregado (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            documento_requerido_id VARCHAR(36) NOT NULL,
            archivo_path VARCHAR(500) NOT NULL,
            estado VARCHAR(20) DEFAULT \'entregado\' NOT NULL,
            entregado_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_EXP_DOC_ENTREGADO ON expediente_documento_entregado (expediente_id, documento_requerido_id)');

        $this->addSql('CREATE TABLE IF NOT EXISTS expediente_firma_documento (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            tipo_escrito VARCHAR(30) NOT NULL,
            firma_png_path VARCHAR(500) NOT NULL,
            pdf_firmado_path VARCHAR(500) DEFAULT NULL,
            firmado_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            user_agent VARCHAR(500) DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_EXP_FIRMA_TIPO ON expediente_firma_documento (expediente_id, tipo_escrito)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS expediente_firma_documento');
        $this->addSql('DROP TABLE IF EXISTS expediente_documento_entregado');
        $this->addSql('DROP TABLE IF EXISTS expediente_hito');
        $this->addSql('DROP TABLE IF EXISTS expediente_contratacion_paso');

        $this->addSql('ALTER TABLE cliente DROP COLUMN IF EXISTS holded_contact_id');
        $this->addSql('ALTER TABLE cliente DROP COLUMN IF EXISTS holded_estado');
        $this->addSql('ALTER TABLE cliente DROP COLUMN IF EXISTS holded_synced_at');
        $this->addSql('ALTER TABLE cliente DROP COLUMN IF EXISTS holded_sync_error');
        $this->addSql('ALTER TABLE cliente DROP COLUMN IF EXISTS documento_identidad_tipo');
        $this->addSql('ALTER TABLE cliente DROP COLUMN IF EXISTS documento_identidad_anverso_path');
        $this->addSql('ALTER TABLE cliente DROP COLUMN IF EXISTS documento_identidad_reverso_path');
        $this->addSql('ALTER TABLE cliente DROP COLUMN IF EXISTS documento_identidad_escaneado_at');

        $this->addSql('ALTER TABLE expediente DROP COLUMN IF EXISTS fecha_vencimiento_fase');
        $this->addSql('ALTER TABLE expediente DROP COLUMN IF EXISTS fecha_ultimo_cambio_estado');
    }
}
