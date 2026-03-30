<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Índice único insensible a mayúsculas en nombre de tipo_caso (deduplica filas previas)';
    }

    public function up(Schema $schema): void
    {
        // Misma regla que en aplicación: un nombre por LOWER(TRIM(nombre)). Se conserva una fila por clave (id menor).
        $this->addSql(<<<'SQL'
DELETE FROM tipo_caso
WHERE id IN (
    SELECT id FROM (
        SELECT id,
            ROW_NUMBER() OVER (PARTITION BY LOWER(TRIM(nombre)) ORDER BY id) AS rn
        FROM tipo_caso
    ) d
    WHERE d.rn > 1
)
SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_tipo_caso_nombre_lower ON tipo_caso (LOWER(TRIM(nombre)))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_tipo_caso_nombre_lower');
    }
}
