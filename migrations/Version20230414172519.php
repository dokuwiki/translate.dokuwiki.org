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
        //update database, needed?
        $this->addSql('ALTER DATABASE translate CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;');

        //converting a table will changes all columns. If deviating char sets per columns, per column change would be needed.

        $this->addSql('ALTER TABLE doctrine_migration_versions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');

        $this->addSql('SET FOREIGN_KEY_CHECKS=0;');
        $this->addSql('ALTER TABLE languageStats CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        $this->addSql('ALTER TABLE languageName CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        $this->addSql('ALTER TABLE repository CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        $this->addSql('ALTER TABLE translationUpdate CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(Schema $schema): void
    {

        //update database, needed?
        $this->addSql('ALTER DATABASE translate CHARACTER SET = utf8 COLLATE = utf8_unicode_ci;');

        //convert table, changes all columns. If deviating columns, per column change would be needed.
        $this->addSql('ALTER TABLE doctrine_migration_versions CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;');

        $this->addSql('SET FOREIGN_KEY_CHECKS=0;');
        $this->addSql('ALTER TABLE languageStats CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;');
        $this->addSql('ALTER TABLE languageName CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;');
        $this->addSql('ALTER TABLE repository CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;');
        $this->addSql('ALTER TABLE translationUpdate CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1;');
    }
}
