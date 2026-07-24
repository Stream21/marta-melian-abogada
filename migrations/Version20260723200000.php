<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normaliza estados de expediente: cerrado/archivado → finalizado; introduce cancelado.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE expediente SET estado = 'finalizado' WHERE estado IN ('cerrado', 'archivado')");
    }

    public function down(Schema $schema): void
    {
        // No reversible de forma fiable: finalizado no distingue cerrado vs archivado.
    }
}
