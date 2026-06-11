<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Application\Service\DespachoMembreteDefaults;
use App\Domain\Entity\DespachoConfig;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Pie con ondas e iconos + datos de contacto de prueba del despacho';
    }

    public function up(Schema $schema): void
    {
        $id = $this->connection->quote(DespachoConfig::DEFAULT_ID);
        $pie = $this->connection->quote(DespachoMembreteDefaults::pieHtml());
        $direccion = $this->connection->quote('C. Picachos, 43, local 2, 35200 Telde, Las Palmas, España');
        $ciudad = $this->connection->quote('Las Palmas de Gran Canaria');
        $telefono = $this->connection->quote('+34 652 292 450');
        $email = $this->connection->quote('mmguerra.abogada@gmail.com');
        $web = $this->connection->quote('https://martamelianguerraabogados.com/');

        $this->addSql(sprintf(
            'UPDATE despacho_config SET direccion = %s, ciudad = %s, telefono = %s, email = %s, web = %s, pie_html = %s WHERE id = %s',
            $direccion,
            $ciudad,
            $telefono,
            $email,
            $web,
            $pie,
            $id,
        ));
    }

    public function down(Schema $schema): void
    {
    }
}
