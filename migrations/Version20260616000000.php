<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_project table and migrate roles to ROLE_USUARIO/ROLE_ADMIN';
    }

    public function up(Schema $schema): void
    {
        $isSqlite = $this->connection->getDatabasePlatform() instanceof SqlitePlatform;

        if ($isSqlite) {
            $this->addSql('CREATE TABLE user_project (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id INTEGER NOT NULL,
                project_id INTEGER NOT NULL,
                can_manage_content_types BOOLEAN DEFAULT 0 NOT NULL,
                CONSTRAINT FK_USER_PROJECT_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
                CONSTRAINT FK_USER_PROJECT_PROJECT FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )');
        } else {
            $this->addSql('CREATE TABLE user_project (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                project_id INT NOT NULL,
                can_manage_content_types TINYINT(1) DEFAULT 0 NOT NULL,
                INDEX IDX_USER_PROJECT_USER (user_id),
                INDEX IDX_USER_PROJECT_PROJECT (project_id),
                UNIQUE INDEX user_project_unique (user_id, project_id),
                CONSTRAINT FK_USER_PROJECT_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                CONSTRAINT FK_USER_PROJECT_PROJECT FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if ($isSqlite) {
            $this->addSql('CREATE INDEX IDX_USER_PROJECT_USER ON user_project (user_id)');
            $this->addSql('CREATE INDEX IDX_USER_PROJECT_PROJECT ON user_project (project_id)');
            $this->addSql('CREATE UNIQUE INDEX user_project_unique ON user_project (user_id, project_id)');
        }

        $this->migrateRoles();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_project');

        $rows = $this->connection->fetchAllAssociative('SELECT id, roles FROM users');
        foreach ($rows as $row) {
            $roles = json_decode($row['roles'], true) ?? [];
            $roles = array_map(static fn (string $role): string => match ($role) {
                'ROLE_ADMIN' => 'ROLE_SUPER_ADMIN',
                'ROLE_USUARIO' => 'ROLE_USER',
                default => $role,
            }, $roles);
            $this->connection->update('users', ['roles' => json_encode(array_values(array_unique($roles)))], ['id' => $row['id']]);
        }
    }

    private function migrateRoles(): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, roles FROM users');
        foreach ($rows as $row) {
            $roles = json_decode($row['roles'], true) ?? [];
            $roles = array_map(static fn (string $role): string => match ($role) {
                'ROLE_SUPER_ADMIN' => 'ROLE_ADMIN',
                'ROLE_USER' => 'ROLE_USUARIO',
                default => $role,
            }, $roles);
            $roles = array_values(array_unique($roles));
            $this->connection->update('users', ['roles' => json_encode($roles)], ['id' => $row['id']]);
        }
    }
}
