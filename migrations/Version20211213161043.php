<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211213161043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mute (id INT AUTO_INCREMENT NOT NULL, user_name VARCHAR(255) NOT NULL, reason VARCHAR(255) NOT NULL, duration INT NOT NULL, created DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mute_channels (mute_id INT NOT NULL, channel_id INT NOT NULL, INDEX IDX_B8AA577BB5483335 (mute_id), INDEX IDX_B8AA577B72F5A1AA (channel_id), PRIMARY KEY(mute_id, channel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mute_channels ADD CONSTRAINT FK_B8AA577BB5483335 FOREIGN KEY (mute_id) REFERENCES mute (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mute_channels ADD CONSTRAINT FK_B8AA577B72F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mute_channels DROP FOREIGN KEY FK_B8AA577BB5483335');
        $this->addSql('DROP TABLE mute');
        $this->addSql('DROP TABLE mute_channels');
    }
}
