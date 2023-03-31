<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230330213934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Import all tables';
    }

    public function up(Schema $schema): void
    {
        if ($this->sm->tablesExist('repository')) {
            return;
        }
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE languageName (code VARCHAR(50) NOT NULL, name VARCHAR(150) NOT NULL, rtl TINYINT(1) NOT NULL, PRIMARY KEY(code)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE languageStats (id INT AUTO_INCREMENT NOT NULL, language VARCHAR(50) DEFAULT NULL, repository_id INT DEFAULT NULL, completionPercent INT NOT NULL, INDEX IDX_F9AAD2F9D4DB71B5 (language), INDEX IDX_F9AAD2F950C9D4F7 (repository_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE repository (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(300) NOT NULL, branch VARCHAR(100) NOT NULL, lastUpdate INT NOT NULL, name VARCHAR(100) NOT NULL, popularity INT NOT NULL, displayName VARCHAR(200) NOT NULL, email VARCHAR(355) NOT NULL, author VARCHAR(100) NOT NULL, description VARCHAR(500) NOT NULL, tags VARCHAR(200) NOT NULL, type VARCHAR(50) NOT NULL, state VARCHAR(100) NOT NULL, errorMsg VARCHAR(255) NOT NULL, errorCount INT NOT NULL, activationKey VARCHAR(100) NOT NULL, englishReadonly TINYINT(1) NOT NULL, INDEX name_idx (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE translationUpdate (id INT AUTO_INCREMENT NOT NULL, repository_id INT DEFAULT NULL, author VARCHAR(300) NOT NULL, email VARCHAR(300) NOT NULL, updated INT NOT NULL, state VARCHAR(300) NOT NULL, language VARCHAR(100) NOT NULL, INDEX IDX_7241B4BD50C9D4F7 (repository_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE languageStats ADD CONSTRAINT FK_F9AAD2F9D4DB71B5 FOREIGN KEY (language) REFERENCES languageName (code)');
        $this->addSql('ALTER TABLE languageStats ADD CONSTRAINT FK_F9AAD2F950C9D4F7 FOREIGN KEY (repository_id) REFERENCES repository (id)');
        $this->addSql('ALTER TABLE translationUpdate ADD CONSTRAINT FK_7241B4BD50C9D4F7 FOREIGN KEY (repository_id) REFERENCES repository (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE languageStats DROP FOREIGN KEY FK_F9AAD2F9D4DB71B5');
        $this->addSql('ALTER TABLE languageStats DROP FOREIGN KEY FK_F9AAD2F950C9D4F7');
        $this->addSql('ALTER TABLE translationUpdate DROP FOREIGN KEY FK_7241B4BD50C9D4F7');
        $this->addSql('DROP TABLE languageName');
        $this->addSql('DROP TABLE languageStats');
        $this->addSql('DROP TABLE repository');
        $this->addSql('DROP TABLE translationUpdate');
    }
}
