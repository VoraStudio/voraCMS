<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260626060117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE visit (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, path VARCHAR(255) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL, visited_at DATETIME NOT NULL, client_id INTEGER NOT NULL, entry_id INTEGER DEFAULT NULL, CONSTRAINT FK_437EE93919EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_437EE939BA364942 FOREIGN KEY (entry_id) REFERENCES entries (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_437EE93919EB6921 ON visit (client_id)');
        $this->addSql('CREATE INDEX IDX_437EE939BA364942 ON visit (entry_id)');
        $this->addSql('CREATE INDEX IDX_437EE93919EB6921EDA764E3 ON visit (client_id, visited_at)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_project AS SELECT id, user_id, project_id, can_manage_content_types FROM user_project');
        $this->addSql('DROP TABLE user_project');
        $this->addSql('CREATE TABLE user_project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, project_id INTEGER NOT NULL, can_manage_content_types BOOLEAN DEFAULT 0 NOT NULL, CONSTRAINT FK_77BECEE4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_77BECEE4166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user_project (id, user_id, project_id, can_manage_content_types) SELECT id, user_id, project_id, can_manage_content_types FROM __temp__user_project');
        $this->addSql('DROP TABLE __temp__user_project');
        $this->addSql('CREATE UNIQUE INDEX user_project_unique ON user_project (user_id, project_id)');
        $this->addSql('CREATE INDEX IDX_77BECEE4A76ED395 ON user_project (user_id)');
        $this->addSql('CREATE INDEX IDX_77BECEE4166D1F9C ON user_project (project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE visit');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_project AS SELECT id, can_manage_content_types, user_id, project_id FROM user_project');
        $this->addSql('DROP TABLE user_project');
        $this->addSql('CREATE TABLE user_project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, can_manage_content_types BOOLEAN DEFAULT 0 NOT NULL, user_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_USER_PROJECT_USER FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_USER_PROJECT_PROJECT FOREIGN KEY (project_id) REFERENCES projects (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user_project (id, can_manage_content_types, user_id, project_id) SELECT id, can_manage_content_types, user_id, project_id FROM __temp__user_project');
        $this->addSql('DROP TABLE __temp__user_project');
        $this->addSql('CREATE UNIQUE INDEX user_project_unique ON user_project (user_id, project_id)');
        $this->addSql('CREATE INDEX IDX_USER_PROJECT_PROJECT ON user_project (project_id)');
        $this->addSql('CREATE INDEX IDX_USER_PROJECT_USER ON user_project (user_id)');
    }
}
