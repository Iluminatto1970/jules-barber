#!/bin/sh
set -e

APP_DB_PASS="${APP_DB_PASS:-app123}"

mariadb -uroot -p"${MARIADB_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS barbearia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS barbearia_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'barbearia_app'@'%' IDENTIFIED BY '${APP_DB_PASS}';
GRANT ALL PRIVILEGES ON barbearia.* TO 'barbearia_app'@'%';
GRANT ALL PRIVILEGES ON barbearia_saas.* TO 'barbearia_app'@'%';
FLUSH PRIVILEGES;
SQL

mariadb -uroot -p"${MARIADB_ROOT_PASSWORD}" barbearia < /seed/barbearia.sql
mariadb -uroot -p"${MARIADB_ROOT_PASSWORD}" barbearia_saas < /seed/barbearia_saas.sql

mariadb -uroot -p"${MARIADB_ROOT_PASSWORD}" <<SQL
UPDATE barbearia_saas.empresas
SET db_host = 'db',
    db_usuario = 'barbearia_app',
    db_senha = '${APP_DB_PASS}'
WHERE ativo = 'Sim';
SQL
