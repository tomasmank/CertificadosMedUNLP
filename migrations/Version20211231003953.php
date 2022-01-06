<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211231003953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attendee DROP email, DROP cond');
        $this->addSql('ALTER TABLE event_attendee ADD id INT AUTO_INCREMENT NOT NULL, ADD email VARCHAR(320) NOT NULL, ADD cond VARCHAR(255) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attendee ADD email VARCHAR(320) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD cond VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE event_attendee MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE event_attendee DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE event_attendee DROP id, DROP email, DROP cond');
        $this->addSql('ALTER TABLE event_attendee ADD PRIMARY KEY (event_id, attendee_id)');
    }
}
