<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250228120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create expediente table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE expediente (
            id VARCHAR(36) NOT NULL,
            numero VARCHAR(100) NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            estado VARCHAR(20) NOT NULL,
            fecha_apertura TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE expediente');
    }
}
