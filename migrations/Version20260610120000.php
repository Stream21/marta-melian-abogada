<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Membrete despacho, cliente, escrito_plantilla, tramite en expediente';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE despacho_config ADD subtitulo_profesional VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE despacho_config ADD telefono VARCHAR(50) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE despacho_config ADD email VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE despacho_config ADD web VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE despacho_config ADD nif VARCHAR(20) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE despacho_config ADD colegio_abogados VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE despacho_config ADD iban VARCHAR(34) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE despacho_config ADD entidad_bancaria VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE despacho_config ADD titular_cuenta VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE despacho_config ADD membrete_html TEXT DEFAULT NULL');

        $this->addSql('CREATE TABLE cliente (
            id VARCHAR(36) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            nacionalidad VARCHAR(100) DEFAULT \'\' NOT NULL,
            tipo_documento VARCHAR(50) DEFAULT \'\' NOT NULL,
            num_documento VARCHAR(50) DEFAULT \'\' NOT NULL,
            fecha_nacimiento DATE DEFAULT NULL,
            lugar_nacimiento VARCHAR(255) DEFAULT \'\' NOT NULL,
            domicilio VARCHAR(500) DEFAULT \'\' NOT NULL,
            codigo_postal VARCHAR(20) DEFAULT \'\' NOT NULL,
            ciudad VARCHAR(100) DEFAULT \'\' NOT NULL,
            telefono VARCHAR(50) DEFAULT \'\' NOT NULL,
            email VARCHAR(255) DEFAULT \'\' NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('ALTER TABLE expediente ADD cliente_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE expediente ADD tramite_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE expediente ADD CONSTRAINT FK_EXPEDIENTE_CLIENTE FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expediente ADD CONSTRAINT FK_EXPEDIENTE_TRAMITE FOREIGN KEY (tramite_id) REFERENCES tramite (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_EXPEDIENTE_CLIENTE ON expediente (cliente_id)');
        $this->addSql('CREATE INDEX IDX_EXPEDIENTE_TRAMITE ON expediente (tramite_id)');

        $this->addSql('CREATE TABLE escrito_plantilla (
            id VARCHAR(36) NOT NULL,
            tramite_id VARCHAR(36) NOT NULL,
            tipo VARCHAR(30) NOT NULL,
            bloques JSON NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ESCRITO_PLANTILLA_TRAMITE_TIPO ON escrito_plantilla (tramite_id, tipo)');
        $this->addSql('ALTER TABLE escrito_plantilla ADD CONSTRAINT FK_ESCRITO_PLANTILLA_TRAMITE FOREIGN KEY (tramite_id) REFERENCES tramite (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('INSERT INTO escrito_plantilla (id, tramite_id, tipo, bloques, created_at, updated_at)
            SELECT id, tramite_id, \'hoja_encargo\', bloques, created_at, updated_at FROM hoja_encargo_plantilla');

        $this->addSql('ALTER TABLE hoja_encargo_plantilla DROP CONSTRAINT FK_HE_PLANTILLA_TRAMITE');
        $this->addSql('DROP TABLE hoja_encargo_plantilla');
    }

    public function down(Schema $schema): void
    {
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

        $this->addSql('INSERT INTO hoja_encargo_plantilla (id, tramite_id, bloques, created_at, updated_at)
            SELECT id, tramite_id, bloques, created_at, updated_at FROM escrito_plantilla WHERE tipo = \'hoja_encargo\'');

        $this->addSql('ALTER TABLE escrito_plantilla DROP CONSTRAINT FK_ESCRITO_PLANTILLA_TRAMITE');
        $this->addSql('DROP TABLE escrito_plantilla');

        $this->addSql('ALTER TABLE expediente DROP CONSTRAINT FK_EXPEDIENTE_CLIENTE');
        $this->addSql('ALTER TABLE expediente DROP CONSTRAINT FK_EXPEDIENTE_TRAMITE');
        $this->addSql('DROP INDEX IDX_EXPEDIENTE_CLIENTE');
        $this->addSql('DROP INDEX IDX_EXPEDIENTE_TRAMITE');
        $this->addSql('ALTER TABLE expediente DROP cliente_id');
        $this->addSql('ALTER TABLE expediente DROP tramite_id');

        $this->addSql('DROP TABLE cliente');

        $this->addSql('ALTER TABLE despacho_config DROP subtitulo_profesional');
        $this->addSql('ALTER TABLE despacho_config DROP telefono');
        $this->addSql('ALTER TABLE despacho_config DROP email');
        $this->addSql('ALTER TABLE despacho_config DROP web');
        $this->addSql('ALTER TABLE despacho_config DROP nif');
        $this->addSql('ALTER TABLE despacho_config DROP colegio_abogados');
        $this->addSql('ALTER TABLE despacho_config DROP iban');
        $this->addSql('ALTER TABLE despacho_config DROP entidad_bancaria');
        $this->addSql('ALTER TABLE despacho_config DROP titular_cuenta');
        $this->addSql('ALTER TABLE despacho_config DROP membrete_html');
    }
}
