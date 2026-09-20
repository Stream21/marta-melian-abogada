<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260919023000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Preferencia de canales de notificación al cliente en expediente (WhatsApp/email).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente ADD COLUMN IF NOT EXISTS canales_notificacion JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente DROP COLUMN IF EXISTS canales_notificacion');
    }
}
