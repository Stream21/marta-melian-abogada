<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Eliminar paso fantasma documentacion de contratación (flujo simplificado a 3 pasos)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM expediente_contratacion_paso WHERE paso = 'documentacion'");
    }

    public function down(Schema $schema): void
    {
        // No se restaura: el paso documentacion quedó absorbido por datos_cliente
    }
}
