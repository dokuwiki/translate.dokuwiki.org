<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230414145940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'update to recenter naming scheme in doctrine.yaml:  naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware';
    }

    public function up(Schema $schema): void
    {
        // cleanup old migration table id existing
        $this->addSql('DROP TABLE IF EXISTS migration_versions');

        $this->addSql('ALTER TABLE languageStats 
                                CHANGE completionpercent completion_percent INT NOT NULL');
        $this->addSql('ALTER TABLE repository 
                                CHANGE lastUpdate last_update INT NOT NULL, 
                                CHANGE errorCount error_count INT NOT NULL, 
                                CHANGE displayname display_name VARCHAR(200) NOT NULL, 
                                CHANGE errormsg error_msg LONGTEXT NOT NULL, 
                                CHANGE activationkey activation_key VARCHAR(100) NOT NULL, 
                                CHANGE englishreadonly english_readonly TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        //migration_versions was table for older doctrine migrations v2, do not restore.
//        $this->addSql('CREATE TABLE migration_versions (version VARCHAR(14) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, executed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(version)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');

        $this->addSql('ALTER TABLE languageStats 
                               CHANGE completion_percent completionPercent INT NOT NULL');
        $this->addSql('ALTER TABLE repository 
                               CHANGE last_update lastUpdate INT NOT NULL, 
                               CHANGE error_count errorCount INT NOT NULL, 
                               CHANGE display_name displayName VARCHAR(200) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, 
                               CHANGE error_msg errorMsg LONGTEXT CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, 
                               CHANGE activation_key activationKey VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, 
                               CHANGE english_readonly englishReadonly TINYINT(1) NOT NULL');
    }
}
