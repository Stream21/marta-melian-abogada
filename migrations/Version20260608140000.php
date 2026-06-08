<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add penal service type and fix existing Derecho Penal rows';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE servicio SET tipo = 'penal' WHERE LOWER(nombre) LIKE '%penal%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE servicio SET tipo = 'civil_contratacion' WHERE tipo = 'penal'");
    }
}
