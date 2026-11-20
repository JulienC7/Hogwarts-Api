<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261120124616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE baguette (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, description VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE espece (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(100) NOT NULL)');
        $this->addSql('CREATE TABLE maison (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(50) NOT NULL)');
        $this->addSql('CREATE TABLE patronus (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(100) NOT NULL)');
        $this->addSql('CREATE TABLE personnage (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, hogwarts_id VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, genre VARCHAR(50) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, maison_id INTEGER DEFAULT NULL, espece_id INTEGER DEFAULT NULL, patronus_id INTEGER DEFAULT NULL, CONSTRAINT FK_6AEA486D9D67D8AF FOREIGN KEY (maison_id) REFERENCES maison (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6AEA486D2D191E7A FOREIGN KEY (espece_id) REFERENCES espece (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6AEA486D7602A6AC FOREIGN KEY (patronus_id) REFERENCES patronus (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6AEA486D9D67D8AF ON personnage (maison_id)');
        $this->addSql('CREATE INDEX IDX_6AEA486D2D191E7A ON personnage (espece_id)');
        $this->addSql('CREATE INDEX IDX_6AEA486D7602A6AC ON personnage (patronus_id)');
        $this->addSql('CREATE TABLE personnage_baguette (personnage_id INTEGER NOT NULL, baguette_id INTEGER NOT NULL, PRIMARY KEY (personnage_id, baguette_id), CONSTRAINT FK_A04EAE955E315342 FOREIGN KEY (personnage_id) REFERENCES personnage (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A04EAE95513FF34B FOREIGN KEY (baguette_id) REFERENCES baguette (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A04EAE955E315342 ON personnage_baguette (personnage_id)');
        $this->addSql('CREATE INDEX IDX_A04EAE95513FF34B ON personnage_baguette (baguette_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE baguette');
        $this->addSql('DROP TABLE espece');
        $this->addSql('DROP TABLE maison');
        $this->addSql('DROP TABLE patronus');
        $this->addSql('DROP TABLE personnage');
        $this->addSql('DROP TABLE personnage_baguette');
    }
}
