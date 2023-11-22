<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230414172519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert to a new char set';
    }


    public function up(Schema $schema): void
    {

        $this->addSql('ALTER TABLE languageStats DROP FOREIGN KEY FK_F9AAD2F9D4DB71B5');
        $this->addSql('ALTER TABLE languageStats DROP FOREIGN KEY FK_F9AAD2F950C9D4F7');
        $this->addSql('ALTER TABLE translationUpdate DROP FOREIGN KEY FK_7241B4BD50C9D4F7');

        $this->addSql('ALTER DATABASE translate CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;');
        $this->addSql('ALTER TABLE doctrine_migration_versions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');

        $this->addSql('ALTER TABLE languageStats CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        $this->addSql('ALTER TABLE languageName CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        $this->addSql('ALTER TABLE repository CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        $this->addSql('ALTER TABLE translationUpdate CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');

        $this->addSql('ALTER TABLE languageStats ADD CONSTRAINT FK_F9AAD2F9D4DB71B5 FOREIGN KEY (language) REFERENCES languageName (code)');
        $this->addSql('ALTER TABLE languageStats ADD CONSTRAINT FK_F9AAD2F950C9D4F7 FOREIGN KEY (repository_id) REFERENCES repository (id)');
        $this->addSql('ALTER TABLE translationUpdate ADD CONSTRAINT FK_7241B4BD50C9D4F7 FOREIGN KEY (repository_id) REFERENCES repository (id)');
    }

    public function down(Schema $schema): void
    {

        $this->addSql('ALTER TABLE languageStats DROP FOREIGN KEY FK_F9AAD2F9D4DB71B5');
        $this->addSql('ALTER TABLE languageStats DROP FOREIGN KEY FK_F9AAD2F950C9D4F7');
        $this->addSql('ALTER TABLE translationUpdate DROP FOREIGN KEY FK_7241B4BD50C9D4F7');

        $this->addSql('ALTER DATABASE translate CHARACTER SET = utf8 COLLATE = utf8_unicode_ci;');
        $this->addSql('ALTER TABLE doctrine_migration_versions CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;');

        $this->addSql('ALTER TABLE languageStats CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;');
        $this->addSql('ALTER TABLE languageName CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;');
        $this->addSql('ALTER TABLE repository CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;');
        $this->addSql('ALTER TABLE translationUpdate CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;');

        $this->addSql('ALTER TABLE languageStats ADD CONSTRAINT FK_F9AAD2F9D4DB71B5 FOREIGN KEY (language) REFERENCES languageName (code)');
        $this->addSql('ALTER TABLE languageStats ADD CONSTRAINT FK_F9AAD2F950C9D4F7 FOREIGN KEY (repository_id) REFERENCES repository (id)');
        $this->addSql('ALTER TABLE translationUpdate ADD CONSTRAINT FK_7241B4BD50C9D4F7 FOREIGN KEY (repository_id) REFERENCES repository (id)');
    }
}
