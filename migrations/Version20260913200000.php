<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260913200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Requerimiento Mercurio: documentos, campos de formulario y plantillas servicio/trámite.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS expediente_requerimiento_documento (
                id VARCHAR(36) NOT NULL,
                requerimiento_id VARCHAR(36) NOT NULL,
                nombre VARCHAR(255) NOT NULL,
                descripcion TEXT NOT NULL,
                responsable VARCHAR(20) NOT NULL,
                obligatorio BOOLEAN NOT NULL DEFAULT true,
                estado VARCHAR(20) NOT NULL,
                archivo_path VARCHAR(500) DEFAULT NULL,
                nota_rechazo TEXT DEFAULT NULL,
                orden INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_req_doc_requerimiento ON expediente_requerimiento_documento (requerimiento_id)');
        $this->addSql("COMMENT ON COLUMN expediente_requerimiento_documento.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN expediente_requerimiento_documento.updated_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS expediente_requerimiento_campo (
                id VARCHAR(36) NOT NULL,
                requerimiento_id VARCHAR(36) NOT NULL,
                clave VARCHAR(100) NOT NULL,
                etiqueta VARCHAR(255) NOT NULL,
                tipo VARCHAR(20) NOT NULL,
                opciones_json JSON DEFAULT NULL,
                obligatorio BOOLEAN NOT NULL DEFAULT true,
                orden INT NOT NULL DEFAULT 0,
                valor TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_req_campo_requerimiento ON expediente_requerimiento_campo (requerimiento_id)');
        $this->addSql("COMMENT ON COLUMN expediente_requerimiento_campo.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN expediente_requerimiento_campo.updated_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS servicio_campo_formulario (
                id VARCHAR(36) NOT NULL,
                servicio_id VARCHAR(36) NOT NULL,
                clave VARCHAR(100) NOT NULL,
                etiqueta VARCHAR(255) NOT NULL,
                tipo VARCHAR(20) NOT NULL,
                opciones_json JSON DEFAULT NULL,
                obligatorio BOOLEAN NOT NULL DEFAULT true,
                orden INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_servicio_campo_formulario ON servicio_campo_formulario (servicio_id)');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS tramite_campo_formulario (
                id VARCHAR(36) NOT NULL,
                tramite_id VARCHAR(36) NOT NULL,
                clave VARCHAR(100) NOT NULL,
                etiqueta VARCHAR(255) NOT NULL,
                tipo VARCHAR(20) NOT NULL,
                opciones_json JSON DEFAULT NULL,
                obligatorio BOOLEAN NOT NULL DEFAULT true,
                orden INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_tramite_campo_formulario ON tramite_campo_formulario (tramite_id)');
        $this->addSql("COMMENT ON COLUMN servicio_campo_formulario.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN servicio_campo_formulario.updated_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN tramite_campo_formulario.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN tramite_campo_formulario.updated_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS tramite_campo_formulario');
        $this->addSql('DROP TABLE IF EXISTS servicio_campo_formulario');
        $this->addSql('DROP TABLE IF EXISTS expediente_requerimiento_campo');
        $this->addSql('DROP TABLE IF EXISTS expediente_requerimiento_documento');
    }
}
