SHELL := /bin/bash

.PHONY: up down restart logs ps reset smoke

up:
	docker compose up -d --build

down:
	docker compose down

restart:
	docker compose down
	docker compose up -d --build

logs:
	docker compose logs -f app db

ps:
	docker compose ps

reset:
	docker compose down -v
	docker compose up -d --build

smoke:
	bash scripts/smoke.sh
