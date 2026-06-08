<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\Persistence\Migration\ExtranjeriaCatalogSeedData;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608181000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed extranjería y nacionalidad catalog (9 servicios, 40 trámites)';
    }

    public function up(Schema $schema): void
    {
        $areaId = ExtranjeriaCatalogSeedData::AREA_ID;

        foreach (ExtranjeriaCatalogSeedData::servicios() as $servicio) {
            $nombreServicio = $this->escape($servicio['nombre']);
            $this->addSql(<<<SQL
INSERT INTO servicio (id, nombre, area_juridica_id, activo)
SELECT '{$servicio['id']}', '{$nombreServicio}', '{$areaId}', true
WHERE NOT EXISTS (
    SELECT 1 FROM servicio WHERE LOWER(TRIM(nombre)) = LOWER(TRIM('{$nombreServicio}'))
)
SQL);

            foreach ($servicio['tramites'] as $tramite) {
                $nombreTramite = $this->escape($tramite['nombre']);
                $plataforma = $this->escape($tramite['plataforma']);
                $requiereProcurador = $tramite['requiere_procurador'] ? 'true' : 'false';
                $honorarios = $tramite['honorarios'];

                $this->addSql(<<<SQL
INSERT INTO tramite (id, servicio_id, nombre, honorarios, canal, requiere_procurador, activo)
SELECT '{$tramite['id']}', s.id, '{$nombreTramite}', {$honorarios}, '{$plataforma}', {$requiereProcurador}, true
FROM servicio s
WHERE LOWER(TRIM(s.nombre)) = LOWER(TRIM('{$nombreServicio}'))
  AND NOT EXISTS (
    SELECT 1 FROM tramite t
    WHERE t.servicio_id = s.id
      AND LOWER(TRIM(t.nombre)) = LOWER(TRIM('{$nombreTramite}'))
  )
SQL);
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (ExtranjeriaCatalogSeedData::servicios() as $servicio) {
            foreach ($servicio['tramites'] as $tramite) {
                $this->addSql("DELETE FROM tramite WHERE id = '{$tramite['id']}'");
            }
            $this->addSql("DELETE FROM servicio WHERE id = '{$servicio['id']}'");
        }
    }

    private function escape(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}
