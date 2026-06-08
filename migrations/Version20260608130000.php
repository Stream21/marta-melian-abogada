<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tipo column to servicio (legal area category)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE servicio ADD tipo VARCHAR(50) NOT NULL DEFAULT 'civil_contratacion'");
        $this->addSql("UPDATE servicio SET tipo = 'extranjeria_nacionalidad' WHERE LOWER(nombre) LIKE '%extranjer%'");
        $this->addSql("UPDATE servicio SET tipo = 'familia_sucesiones' WHERE LOWER(nombre) LIKE '%familia%'");
        $this->addSql("UPDATE servicio SET tipo = 'laboral_seguridad_social' WHERE LOWER(nombre) LIKE '%laboral%'");
        $this->addSql("UPDATE servicio SET tipo = 'civil_contratacion' WHERE LOWER(nombre) LIKE '%civil%'");
        $this->addSql("UPDATE servicio SET tipo = 'civil_contratacion' WHERE LOWER(nombre) LIKE '%penal%' OR LOWER(nombre) LIKE '%administrativo%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE servicio DROP COLUMN tipo');
    }
}
