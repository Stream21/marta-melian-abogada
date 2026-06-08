<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\Persistence\Migration\ExtranjeriaCatalogSeedData;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608182000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Simplify extranjería tramite names (remove parenthetical details)';
    }

    public function up(Schema $schema): void
    {
        foreach (ExtranjeriaCatalogSeedData::servicios() as $servicio) {
            foreach ($servicio['tramites'] as $tramite) {
                $nombre = str_replace("'", "''", $tramite['nombre']);
                $this->addSql("UPDATE tramite SET nombre = '{$nombre}' WHERE id = '{$tramite['id']}'");
            }
        }
    }

    public function down(Schema $schema): void
    {
        // No reversible: los nombres largos originales no se conservan.
    }
}
