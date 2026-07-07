<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260701082931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE content_types (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, active TINYINT DEFAULT 1 NOT NULL, base TINYINT DEFAULT 0 NOT NULL, auto_clone TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, project_id INT DEFAULT NULL, INDEX IDX_B2F3DDE2A76ED395 (user_id), INDEX IDX_B2F3DDE2166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE entries (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL, locale VARCHAR(5) DEFAULT \'ca\' NOT NULL, active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, published_at DATE DEFAULT NULL, content_type_id INT NOT NULL, author_id INT DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_2DF8B3C51A445520 (content_type_id), INDEX IDX_2DF8B3C5F675F31B (author_id), INDEX IDX_2DF8B3C5A76ED395 (user_id), INDEX IDX_2DF8B3C51A4455207B00651C (content_type_id, status), INDEX IDX_2DF8B3C51A4455204180C698 (content_type_id, locale), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE field_definitions (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, field_type VARCHAR(50) NOT NULL, required TINYINT DEFAULT 0 NOT NULL, translatable TINYINT DEFAULT 1 NOT NULL, help_text LONGTEXT DEFAULT NULL, sort_order INT DEFAULT 0 NOT NULL, content_type_id INT NOT NULL, INDEX IDX_56D916151A445520 (content_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE field_values (id INT AUTO_INCREMENT NOT NULL, value LONGTEXT DEFAULT NULL, entry_id INT NOT NULL, field_definition_id INT NOT NULL, INDEX IDX_10E3C0E4BA364942 (entry_id), INDEX IDX_10E3C0E44D0FDD48 (field_definition_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE media (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, extension VARCHAR(10) NOT NULL, mime_type VARCHAR(50) NOT NULL, path VARCHAR(255) NOT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, file_size INT NOT NULL, alt_text LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, uploaded_by_id INT DEFAULT NULL, user_id INT NOT NULL, project_id INT DEFAULT NULL, INDEX IDX_6A2CA10CA2B28FE8 (uploaded_by_id), INDEX IDX_6A2CA10CA76ED395 (user_id), INDEX IDX_6A2CA10C166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE projects (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, color VARCHAR(7) DEFAULT \'#4945FF\', active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_5C93B3A4A76ED395 (user_id), UNIQUE INDEX project_slug_user (slug, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, active TINYINT DEFAULT 1 NOT NULL, locale VARCHAR(255) DEFAULT \'ca\' NOT NULL, company VARCHAR(255) DEFAULT NULL, api_token VARCHAR(32) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9989D9B62 (slug), UNIQUE INDEX UNIQ_1483A5E97BA2F5EB (api_token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE visit (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL, visited_at DATETIME NOT NULL, user_id INT NOT NULL, entry_id INT DEFAULT NULL, INDEX IDX_437EE939A76ED395 (user_id), INDEX IDX_437EE939BA364942 (entry_id), INDEX IDX_437EE939A76ED395EDA764E3 (user_id, visited_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE content_types ADD CONSTRAINT FK_B2F3DDE2A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE content_types ADD CONSTRAINT FK_B2F3DDE2166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)');
        $this->addSql('ALTER TABLE entries ADD CONSTRAINT FK_2DF8B3C51A445520 FOREIGN KEY (content_type_id) REFERENCES content_types (id)');
        $this->addSql('ALTER TABLE entries ADD CONSTRAINT FK_2DF8B3C5F675F31B FOREIGN KEY (author_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE entries ADD CONSTRAINT FK_2DF8B3C5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE field_definitions ADD CONSTRAINT FK_56D916151A445520 FOREIGN KEY (content_type_id) REFERENCES content_types (id)');
        $this->addSql('ALTER TABLE field_values ADD CONSTRAINT FK_10E3C0E4BA364942 FOREIGN KEY (entry_id) REFERENCES entries (id)');
        $this->addSql('ALTER TABLE field_values ADD CONSTRAINT FK_10E3C0E44D0FDD48 FOREIGN KEY (field_definition_id) REFERENCES field_definitions (id)');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE visit ADD CONSTRAINT FK_437EE939A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE visit ADD CONSTRAINT FK_437EE939BA364942 FOREIGN KEY (entry_id) REFERENCES entries (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE content_types DROP FOREIGN KEY FK_B2F3DDE2A76ED395');
        $this->addSql('ALTER TABLE content_types DROP FOREIGN KEY FK_B2F3DDE2166D1F9C');
        $this->addSql('ALTER TABLE entries DROP FOREIGN KEY FK_2DF8B3C51A445520');
        $this->addSql('ALTER TABLE entries DROP FOREIGN KEY FK_2DF8B3C5F675F31B');
        $this->addSql('ALTER TABLE entries DROP FOREIGN KEY FK_2DF8B3C5A76ED395');
        $this->addSql('ALTER TABLE field_definitions DROP FOREIGN KEY FK_56D916151A445520');
        $this->addSql('ALTER TABLE field_values DROP FOREIGN KEY FK_10E3C0E4BA364942');
        $this->addSql('ALTER TABLE field_values DROP FOREIGN KEY FK_10E3C0E44D0FDD48');
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10CA2B28FE8');
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10CA76ED395');
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10C166D1F9C');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A4A76ED395');
        $this->addSql('ALTER TABLE visit DROP FOREIGN KEY FK_437EE939A76ED395');
        $this->addSql('ALTER TABLE visit DROP FOREIGN KEY FK_437EE939BA364942');
        $this->addSql('DROP TABLE content_types');
        $this->addSql('DROP TABLE entries');
        $this->addSql('DROP TABLE field_definitions');
        $this->addSql('DROP TABLE field_values');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE projects');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE visit');
    }
}
