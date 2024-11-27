<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230902083619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE announcement (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, text LONGTEXT NOT NULL, runhours JSON NOT NULL, run_minutes JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE announcement_channels (announcement_id INT NOT NULL, channel_id INT NOT NULL, INDEX IDX_88596845913AEA17 (announcement_id), INDEX IDX_8859684572F5A1AA (channel_id), PRIMARY KEY(announcement_id, channel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE announcement_channels ADD CONSTRAINT FK_88596845913AEA17 FOREIGN KEY (announcement_id) REFERENCES announcement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE announcement_channels ADD CONSTRAINT FK_8859684572F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE announcement_channels DROP FOREIGN KEY FK_88596845913AEA17');
        $this->addSql('ALTER TABLE announcement_channels DROP FOREIGN KEY FK_8859684572F5A1AA');
        $this->addSql('DROP TABLE announcement');
        $this->addSql('DROP TABLE announcement_channels');
    }
}
