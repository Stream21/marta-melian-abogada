<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create area_juridica reference table and link servicio via FK';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE area_juridica (
            id VARCHAR(36) NOT NULL,
            codigo VARCHAR(50) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_area_juridica_codigo ON area_juridica (codigo)');

        $this->addSql("INSERT INTO area_juridica (id, codigo, nombre) VALUES
            ('a1000001-0001-4000-8000-000000000001', 'extranjeria_nacionalidad', 'Derecho de extranjería y nacionalidad'),
            ('a1000001-0001-4000-8000-000000000002', 'familia_sucesiones', 'Derecho de Familia y Sucesiones'),
            ('a1000001-0001-4000-8000-000000000003', 'civil_contratacion', 'Derecho Civil y Contratación'),
            ('a1000001-0001-4000-8000-000000000004', 'penal', 'Derecho Penal'),
            ('a1000001-0001-4000-8000-000000000005', 'laboral_seguridad_social', 'Derecho laboral y Seguridad Social')");

        $this->addSql('ALTER TABLE servicio ADD area_juridica_id VARCHAR(36) DEFAULT NULL');

        $this->addSql('UPDATE servicio s SET area_juridica_id = aj.id
            FROM area_juridica aj WHERE aj.codigo = s.tipo');

        $this->addSql("UPDATE servicio SET area_juridica_id = 'a1000001-0001-4000-8000-000000000003'
            WHERE area_juridica_id IS NULL");

        $this->addSql('ALTER TABLE servicio ALTER COLUMN area_juridica_id SET NOT NULL');
        $this->addSql('ALTER TABLE servicio ADD CONSTRAINT fk_servicio_area_juridica
            FOREIGN KEY (area_juridica_id) REFERENCES area_juridica (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_servicio_area_juridica_id ON servicio (area_juridica_id)');
        $this->addSql('ALTER TABLE servicio DROP COLUMN tipo');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE servicio ADD tipo VARCHAR(50) NOT NULL DEFAULT 'civil_contratacion'");
        $this->addSql('UPDATE servicio s SET tipo = aj.codigo FROM area_juridica aj WHERE aj.id = s.area_juridica_id');
        $this->addSql('ALTER TABLE servicio DROP CONSTRAINT fk_servicio_area_juridica');
        $this->addSql('DROP INDEX idx_servicio_area_juridica_id');
        $this->addSql('ALTER TABLE servicio DROP COLUMN area_juridica_id');
        $this->addSql('DROP TABLE area_juridica');
    }
}
