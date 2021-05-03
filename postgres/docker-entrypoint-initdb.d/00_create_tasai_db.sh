#!/bin/bash
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE USER tasai WITH PASSWORD 'tasai';
    CREATE DATABASE tasai;
    GRANT ALL PRIVILEGES ON DATABASE tasai TO tasai;

EOSQL