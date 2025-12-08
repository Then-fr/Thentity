up:
	docker compose up -d mysql postgres

down:
	docker compose down

build:
	docker compose build

test-74:
	docker compose run --rm php74 vendor/bin/phpunit

test-82:
	#docker compose run --rm php82 vendor/bin/phpunit
	docker compose run --rm php82 vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/Entity

test-84:
	#docker compose run --rm php84 vendor/bin/phpunit
	docker compose run --rm php84 vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/Entity

test-all:
	docker compose run --rm php74 vendor/bin/phpunit
	docker compose run --rm php82 vendor/bin/phpunit
	docker compose run --rm php84 vendor/bin/phpunit
