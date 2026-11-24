# Hogwarts API

API REST sur l'univers Harry Potter, alimentée par un import depuis la [Hogwarts API](https://hogwarts-api.com).

**Duo :** Julien Clavier, Maxym Melnychuk
**Cours :** Architecture de données, API, plateforme (IIM 26/27, A3)

## Stack

- PHP 8.4+, Symfony 8.1
- API Platform 5
- Doctrine ORM, SQLite

## Modèle

| Entité | Clé naturelle | Relation avec Personnage |
|---|---|---|
| Personnage | `hogwartsId` | |
| Maison | `nom` | ManyToOne |
| Espece | `nom` | ManyToOne |
| Patronus | `nom` | ManyToOne |
| Baguette | `description` | ManyToMany |

## Installation

```bash
git clone https://github.com/JulienC7/Hogwarts-Api.git
cd Hogwarts-Api
composer install
```

Créer un fichier `.env` à la racine :

```dotenv
APP_ENV=dev
APP_SECRET=changeme
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
HOGWARTS_API_URL=https://hogwarts-api.com/api
```

Puis créer la base :

```bash
php bin/console doctrine:migrations:migrate
```

## Import des données

```bash
php bin/console app:import-personnages
```

Importe 100 personnages (pages 1 et 2, 50 par page) selon les règles de l'onglet « Règles ETL » du mapping :

| # | Règle |
|---|---|
| E | `GET /characters?page={page}&page_size=50`, seulement les champs utiles |
| T1 | Sans nom : personnage ignoré |
| T2 | `hogwartsId` déjà en base : ignoré (relancer l'import ne duplique rien) |
| T3 à T5 | Maison, espèce, patronus retrouvés par leur nom, sinon créés |
| T6 | Baguettes : mention entre parenthèses retirée, « Unknown… » ignorées |
| T7 | Nom tronqué à 255 caractères |
| T8 | Genre et image nettoyés, vide = null |
| L | Un `persist` par personnage, un seul `flush` à la fin |

Le code est dans `src/Import/PersonnageImporter.php`, avec les mêmes codes en commentaire.

## API

```bash
symfony serve -d
```

Documentation Swagger : http://127.0.0.1:8000/api/docs

Ressources : `/api/personnages`, `/api/maisons`, `/api/especes`, `/api/patronuses`, `/api/baguettes`

## Tests

```bash
php bin/phpunit
```
