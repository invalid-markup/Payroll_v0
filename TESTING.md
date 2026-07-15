PostgreSQL Test Environment

This project uses PostgreSQL for the production database and the test suite can be run against a local PostgreSQL instance.

Quickstart (using Docker):

1. Start the test Postgres container:

```bash
docker compose -f docker-compose.test.yml up -d
```

2. Wait until Postgres is ready (healthcheck is provided). Then run migrations in the testing DB:

```bash
# ensure composer deps installed and artisan available
php artisan migrate --env=testing
```

3. Run the test suite (phpunit is configured to use PostgreSQL by default via `phpunit.xml`):

```bash
php artisan test
```

Notes:
- `phpunit.xml` sets the test DB to `payroll_test` on `127.0.0.1:5432` with user `postgres` and password `secret`.
- If you prefer to use different credentials, either update `phpunit.xml` or set the corresponding env vars when running tests, for example:

```bash
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=payroll_test DB_USERNAME=postgres DB_PASSWORD=secret php artisan test
```

- Ensure `pdo_pgsql` PHP extension is installed and available to PHP-FPM/CLI.
