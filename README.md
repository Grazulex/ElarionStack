# ElarionStack Framework

Un framework PHP moderne pour créer des API expressives, maintenables et élégantes.

## Vision

ElarionStack est inspiré par la philosophie artisanale de Laravel, offrant une architecture claire, une syntaxe fluide, et une intégration native avec les conventions modernes du développement d'API (REST, JSON:API, GraphQL).

## Prérequis

- PHP 8.5+
- Composer
- Docker & Docker Compose (optionnel)

## Installation

### Avec Docker

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Démarrer le conteneur
docker-compose -f docker-compose-php85.yml up -d

# Entrer dans le conteneur
docker exec -it elarionstack_php85 bash

# Installer les dépendances
composer install
```

### Sans Docker

```bash
# Vérifier la version de PHP
php -v  # Doit être >= 8.5

# Installer les dépendances
composer install

# Démarrer le serveur de développement
php -S localhost:8000 -t public
```

## Structure du Projet

```
├── src/              # Code source du framework
│   ├── Core/         # Noyau du framework
│   ├── Http/         # Router, Request, Response
│   ├── Database/     # Query Builder, ORM
│   ├── Api/          # API Resources, Transformers
│   └── Support/      # Helpers, Collections, Traits
├── tests/            # Tests unitaires et d'intégration
├── config/           # Fichiers de configuration
├── public/           # Point d'entrée public
└── storage/          # Logs, cache
```

## Développement

```bash
# Lancer les tests
composer test

# Analyse statique (PHPStan level 9)
composer analyse

# Formater le code
composer format

# Vérifier la qualité globale
composer quality
```

## Roadmap

- [x] Configuration initiale
- [ ] Container d'injection de dépendances
- [ ] Router HTTP
- [ ] Request/Response PSR-7
- [ ] Query Builder
- [ ] ORM
- [ ] API Resources

Voir [claude.md](./claude.md) pour la roadmap complète.

## License

MIT License

## Status

🚧 **En développement actif** - Version 0.1.0-dev
