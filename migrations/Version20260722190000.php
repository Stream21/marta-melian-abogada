<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\Persistence\Migration\NacionalidadSeedData;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Catálogo de nacionalidades (ISO 3166-1 alpha-3) para selector de cliente';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE nacionalidad (
            id VARCHAR(36) NOT NULL,
            codigo VARCHAR(3) NOT NULL,
            nombre VARCHAR(120) NOT NULL,
            activo BOOLEAN DEFAULT true NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_nacionalidad_codigo ON nacionalidad (codigo)');
        $this->addSql('CREATE INDEX idx_nacionalidad_nombre ON nacionalidad (nombre)');

        $i = 1;
        foreach (NacionalidadSeedData::all() as $item) {
            $id = sprintf('d1000001-0001-4000-8000-%012d', $i);
            $this->addSql(
                'INSERT INTO nacionalidad (id, codigo, nombre, activo) VALUES (?, ?, ?, true)',
                [$id, $item['codigo'], $item['nombre']],
            );
            ++$i;
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE nacionalidad');
    }
}
