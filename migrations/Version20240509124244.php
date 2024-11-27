<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240509124244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE report_channels DROP FOREIGN KEY FK_F3747BCB4BD2A4C0');
        $this->addSql('ALTER TABLE report_channels DROP FOREIGN KEY FK_F3747BCB72F5A1AA');
        $this->addSql('DROP TABLE report_channels');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE report_channels (report_id INT NOT NULL, channel_id INT NOT NULL, INDEX IDX_F3747BCB4BD2A4C0 (report_id), INDEX IDX_F3747BCB72F5A1AA (channel_id), PRIMARY KEY(report_id, channel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE report_channels ADD CONSTRAINT FK_F3747BCB4BD2A4C0 FOREIGN KEY (report_id) REFERENCES report (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE report_channels ADD CONSTRAINT FK_F3747BCB72F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
    }
}
