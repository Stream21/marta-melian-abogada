<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Campos ficha cliente: estado civil, provincia, nombre padre y madre';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE cliente ADD estado_civil VARCHAR(50) DEFAULT '' NOT NULL");
        $this->addSql("ALTER TABLE cliente ADD provincia VARCHAR(100) DEFAULT '' NOT NULL");
        $this->addSql("ALTER TABLE cliente ADD nombre_padre VARCHAR(255) DEFAULT '' NOT NULL");
        $this->addSql("ALTER TABLE cliente ADD nombre_madre VARCHAR(255) DEFAULT '' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cliente DROP estado_civil');
        $this->addSql('ALTER TABLE cliente DROP provincia');
        $this->addSql('ALTER TABLE cliente DROP nombre_padre');
        $this->addSql('ALTER TABLE cliente DROP nombre_madre');
    }
}
