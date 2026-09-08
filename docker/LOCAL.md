# Local Docker run

This stack is intentionally isolated from the production-like Compose file.
It starts Laravel PHP-FPM, Nginx, MySQL, Redis, and phpMyAdmin.

```sh
docker compose -f docker-compose.local.yml up --build
```

Open http://localhost:18000. phpMyAdmin is available at
http://localhost:18002 (server: `mysql`, username: `kara`, password:
`kara_local_only`). The local database is stored in the
`kara_panel_local_mysql` Docker volume and is never shared with the production
Compose database volume.

Run tests from the same isolated app container:

```sh
docker compose -f docker-compose.local.yml exec app php artisan test
```

To stop it:

```sh
docker compose -f docker-compose.local.yml down
```

To remove only the disposable local database volume and recreate it on the
next start:

```sh
docker compose -f docker-compose.local.yml down -v
```

The local stack keeps `AUDIT_EXPORT_ENABLED=false`,
`ELASTICSEARCH_ENABLED=false`, `QUEUE_CONNECTION=sync`, and `MAIL_MAILER=log`.
