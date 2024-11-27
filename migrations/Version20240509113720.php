<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240509113720 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE report (id INT AUTO_INCREMENT NOT NULL, reporter_name VARCHAR(255) NOT NULL, reporter_chat_user_id VARCHAR(255) NOT NULL, reason LONGTEXT NOT NULL, reported_message_link LONGTEXT NOT NULL, reported_name VARCHAR(255) NOT NULL, reported_chat_user_id VARCHAR(255) NOT NULL, created DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', deleted DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE report_channels (report_id INT NOT NULL, channel_id INT NOT NULL, INDEX IDX_F3747BCB4BD2A4C0 (report_id), INDEX IDX_F3747BCB72F5A1AA (channel_id), PRIMARY KEY(report_id, channel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE report_channels ADD CONSTRAINT FK_F3747BCB4BD2A4C0 FOREIGN KEY (report_id) REFERENCES report (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE report_channels ADD CONSTRAINT FK_F3747BCB72F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE report_channels DROP FOREIGN KEY FK_F3747BCB4BD2A4C0');
        $this->addSql('ALTER TABLE report_channels DROP FOREIGN KEY FK_F3747BCB72F5A1AA');
        $this->addSql('DROP TABLE report');
        $this->addSql('DROP TABLE report_channels');
    }
}
