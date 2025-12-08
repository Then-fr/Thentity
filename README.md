# Thentity - Tests Docker Environment
Ce projet contient la configuration Docker pour tester Thentity avec différentes versions de PHP et bases de données.

## Services inclus
- PHP 7.4 / 8.2 / 8.4
- MySQL 8
- PostgreSQL 16
- SQLite (via PDO, pas besoin de container dédié)

## Commandes Makefile
- `make up` : démarre MySQL et PostgreSQL
- `make down` : arrête tous les services
- `make build` : build les containers PHP
- `make test-74` : lance PHPUnit sur PHP 7.4
- `make test-all` : lance PHPUnit sur toutes les versions

docker exec -i thentity-mysql-1 mysql -uroot -proot thentity_test < tests/docker/mysql-init/test_sql.sql
docker exec -i thentity-postgres-1 psql -U postgres -d thentity_test < tests/docker/postgres-init/test_sql.sql