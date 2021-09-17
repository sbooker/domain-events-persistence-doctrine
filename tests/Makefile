all: up install test down

up:
	docker compose -p depd -f ./build/docker-compose.yaml up --build -d

install:
	docker compose -p depd -f ./build/docker-compose.yaml exec app composer install

test:
	docker compose -p depd -f ./build/docker-compose.yaml exec app ./vendor/bin/phpunit --configuration .

stop:
	docker compose -p depd -f ./build/docker-compose.yaml stop
down:
	docker compose -p depd -f ./build/docker-compose.yaml down --rmi all
