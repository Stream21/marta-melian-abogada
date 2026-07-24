<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renombra estado de expediente finalizado → archivado (cerrado/finalizado legacy inclusive).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE expediente SET estado = 'archivado' WHERE estado IN ('finalizado', 'cerrado')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE expediente SET estado = 'finalizado' WHERE estado = 'archivado'");
    }
}
