<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Pasos de contratación e hitos del expediente';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE expediente_contratacion_paso (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            paso VARCHAR(30) NOT NULL,
            estado VARCHAR(30) DEFAULT \'pendiente\' NOT NULL,
            realizado_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            validado_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CONTRATACION_EXPEDIENTE_PASO ON expediente_contratacion_paso (expediente_id, paso)');
        $this->addSql('ALTER TABLE expediente_contratacion_paso ADD CONSTRAINT FK_CONTRATACION_EXPEDIENTE FOREIGN KEY (expediente_id) REFERENCES expediente (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE expediente_hito (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            paso VARCHAR(30) DEFAULT NULL,
            tipo VARCHAR(50) NOT NULL,
            descripcion VARCHAR(500) NOT NULL,
            actor VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_HITO_EXPEDIENTE ON expediente_hito (expediente_id)');
        $this->addSql('ALTER TABLE expediente_hito ADD CONSTRAINT FK_HITO_EXPEDIENTE FOREIGN KEY (expediente_id) REFERENCES expediente (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_hito DROP CONSTRAINT FK_HITO_EXPEDIENTE');
        $this->addSql('DROP TABLE expediente_hito');
        $this->addSql('ALTER TABLE expediente_contratacion_paso DROP CONSTRAINT FK_CONTRATACION_EXPEDIENTE');
        $this->addSql('DROP TABLE expediente_contratacion_paso');
    }
}
