<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrar hoja_encargo_plantilla a escrito_plantilla con tipo';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('escrito_plantilla')) {
            return;
        }

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

        if ($schema->hasTable('hoja_encargo_plantilla')) {
            $this->addSql("INSERT INTO escrito_plantilla (id, tramite_id, tipo, bloques, created_at, updated_at)
                SELECT id, tramite_id, 'hoja_encargo', bloques, created_at, updated_at FROM hoja_encargo_plantilla");
            $this->addSql('ALTER TABLE hoja_encargo_plantilla DROP CONSTRAINT FK_HE_PLANTILLA_TRAMITE');
            $this->addSql('DROP TABLE hoja_encargo_plantilla');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('escrito_plantilla')) {
            return;
        }

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

        $this->addSql("INSERT INTO hoja_encargo_plantilla (id, tramite_id, bloques, created_at, updated_at)
            SELECT id, tramite_id, bloques, created_at, updated_at FROM escrito_plantilla WHERE tipo = 'hoja_encargo'");

        $this->addSql('ALTER TABLE escrito_plantilla DROP CONSTRAINT FK_ESCRITO_PLANTILLA_TRAMITE');
        $this->addSql('DROP TABLE escrito_plantilla');
    }
}
