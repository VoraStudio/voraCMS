<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260630080223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__media AS SELECT id, filename, original_filename, extension, mime_type, path, thumbnail_path, file_size, alt_text, created_at, uploaded_by_id, user_id FROM media');
        $this->addSql('DROP TABLE media');
        $this->addSql('CREATE TABLE media (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, extension VARCHAR(10) NOT NULL, mime_type VARCHAR(50) NOT NULL, path VARCHAR(255) NOT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, file_size INTEGER NOT NULL, alt_text CLOB DEFAULT NULL, created_at DATETIME NOT NULL, uploaded_by_id INTEGER DEFAULT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_6A2CA10CA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6A2CA10CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO media (id, filename, original_filename, extension, mime_type, path, thumbnail_path, file_size, alt_text, created_at, uploaded_by_id, user_id) SELECT id, filename, original_filename, extension, mime_type, path, thumbnail_path, file_size, alt_text, created_at, uploaded_by_id, user_id FROM __temp__media');
        $this->addSql('DROP TABLE __temp__media');
        $this->addSql('CREATE INDEX IDX_6A2CA10CA76ED395 ON media (user_id)');
        $this->addSql('CREATE INDEX IDX_6A2CA10CA2B28FE8 ON media (uploaded_by_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__visit AS SELECT id, path, ip, user_agent, visited_at, user_id, entry_id FROM visit');
        $this->addSql('DROP TABLE visit');
        $this->addSql('CREATE TABLE visit (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, path VARCHAR(255) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL, visited_at DATETIME NOT NULL, user_id INTEGER NOT NULL, entry_id INTEGER DEFAULT NULL, CONSTRAINT FK_437EE939BA364942 FOREIGN KEY (entry_id) REFERENCES entries (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_437EE939A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO visit (id, path, ip, user_agent, visited_at, user_id, entry_id) SELECT id, path, ip, user_agent, visited_at, user_id, entry_id FROM __temp__visit');
        $this->addSql('DROP TABLE __temp__visit');
        $this->addSql('CREATE INDEX IDX_437EE939A76ED395EDA764E3 ON visit (user_id, visited_at)');
        $this->addSql('CREATE INDEX IDX_437EE939A76ED395 ON visit (user_id)');
        $this->addSql('CREATE INDEX IDX_437EE939BA364942 ON visit (entry_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__media AS SELECT id, filename, original_filename, extension, mime_type, path, thumbnail_path, file_size, alt_text, created_at, uploaded_by_id, user_id FROM media');
        $this->addSql('DROP TABLE media');
        $this->addSql('CREATE TABLE media (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, extension VARCHAR(10) NOT NULL, mime_type VARCHAR(50) NOT NULL, path VARCHAR(255) NOT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, file_size INTEGER NOT NULL, alt_text CLOB DEFAULT NULL, created_at DATETIME NOT NULL, uploaded_by_id INTEGER DEFAULT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_6A2CA10CA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6A2CA10CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO media (id, filename, original_filename, extension, mime_type, path, thumbnail_path, file_size, alt_text, created_at, uploaded_by_id, user_id) SELECT id, filename, original_filename, extension, mime_type, path, thumbnail_path, file_size, alt_text, created_at, uploaded_by_id, user_id FROM __temp__media');
        $this->addSql('DROP TABLE __temp__media');
        $this->addSql('CREATE INDEX IDX_6A2CA10CA2B28FE8 ON media (uploaded_by_id)');
        $this->addSql('CREATE INDEX IDX_6A2CA10CA76ED395 ON media (user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__visit AS SELECT id, path, ip, user_agent, visited_at, user_id, entry_id FROM visit');
        $this->addSql('DROP TABLE visit');
        $this->addSql('CREATE TABLE visit (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, path VARCHAR(255) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL, visited_at DATETIME NOT NULL, user_id INTEGER NOT NULL, entry_id INTEGER DEFAULT NULL, CONSTRAINT FK_437EE939BA364942 FOREIGN KEY (entry_id) REFERENCES entries (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_437EE939A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO visit (id, path, ip, user_agent, visited_at, user_id, entry_id) SELECT id, path, ip, user_agent, visited_at, user_id, entry_id FROM __temp__visit');
        $this->addSql('DROP TABLE __temp__visit');
        $this->addSql('CREATE INDEX IDX_437EE939A76ED395 ON visit (user_id)');
        $this->addSql('CREATE INDEX IDX_437EE939BA364942 ON visit (entry_id)');
        $this->addSql('CREATE INDEX IDX_437EE939A76ED395EDA764E3 ON visit (user_id, visited_at)');
    }
}
