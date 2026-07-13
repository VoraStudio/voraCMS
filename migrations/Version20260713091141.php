<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260713091141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE api_request_log (id INT AUTO_INCREMENT NOT NULL, domain VARCHAR(255) NOT NULL, endpoint VARCHAR(255) NOT NULL, method VARCHAR(10) NOT NULL, status_code INT NOT NULL, ip VARCHAR(45) NOT NULL, user_agent LONGTEXT DEFAULT NULL, origin VARCHAR(255) DEFAULT NULL, referer VARCHAR(512) DEFAULT NULL, granted TINYINT DEFAULT NULL, deny_reason VARCHAR(255) DEFAULT NULL, token_jti VARCHAR(255) DEFAULT NULL, response_time_ms INT DEFAULT NULL, x_forwarded_for VARCHAR(255) DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_2862A307A7A91E0B8B8E8428 (domain, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE api_request_log');
    }
}
