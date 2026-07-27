<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260727083518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add content_features to projects, feature to content_types';
    }

    public function up(Schema $schema): void
    {
        // NOTA: JSON DEFAULT no funciona a MariaDB 10.4 (CDMON).
        // PHP ja maneja el fallback a traves de getContentFeatures() ?? []
        $this->addSql('ALTER TABLE projects ADD content_features JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE content_types ADD feature VARCHAR(20) DEFAULT \'custom\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projects DROP content_features');
        $this->addSql('ALTER TABLE content_types DROP feature');
    }
}
