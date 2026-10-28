#!/bin/bash
set -euo pipefail

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname postgres <<SQL
DO \$\$
BEGIN
  IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'sinsocial_app') THEN
    CREATE ROLE sinsocial_app LOGIN PASSWORD '${SINSOCIAL_APP_PASSWORD}' NOSUPERUSER NOBYPASSRLS NOCREATEDB;
  END IF;
END
\$\$;
SELECT 'CREATE DATABASE sinsocial OWNER sinsocial_app'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'sinsocial')\gexec
SQL

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname sinsocial <<SQL
ALTER SCHEMA public OWNER TO sinsocial_app;
GRANT ALL ON SCHEMA public TO sinsocial_app;
SQL
