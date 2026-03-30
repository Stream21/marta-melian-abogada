<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabla tipo_caso para configuración de tipos de expediente';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tipo_caso (
            id VARCHAR(36) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            icon_key VARCHAR(32) NOT NULL,
            numero_servicios INT DEFAULT 0 NOT NULL,
            PRIMARY KEY(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tipo_caso');
    }
}
