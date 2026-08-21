# BibRoomz

# Prerequisites

For local development, the recommended setup is:

- Nix with flakes enabled
- direnv with nix-direnv (optional)
- Docker with the Docker Compose plugin

The Nix dev shell provides PHP, Composer, Node, and `process-compose`. Docker Compose provides the local MariaDB,
Redis, and Mailpit services.

If you are not using Nix, install the host tools yourself:

- PHP (>= 8.3) with the following extensions
    - bcmath
    - curl
    - intl
    - mbstring
    - mysql
    - redis (required for the default Redis-backed cache, session, and queue config)
    - xml
    - zip
- Composer
- Node 22 or newer
- process-compose

In addition, a database is required. Currently, only MariaDB is supported (others have not been tested).

## Nix
Enter the development shell manually:

```bash
nix develop
```

The shell adds `vendor/bin` and `node_modules/.bin` to `PATH` and sets `PC_CONFIG_FILES` to `process-compose.yaml`.

## direnv
This repository includes an `.envrc` that loads the Nix flake. With direnv installed, allow it once per checkout:

```bash
direnv allow
```

After that, entering the project directory automatically loads the same environment as `nix develop`. If `use flake` is
not available in your direnv setup, install nix-direnv or run `nix develop` manually.

## Docker Compose
This repository has two Docker Compose-related workflows:

- `process-compose.yaml` runs PHP, Vite, Reverb, the queue worker, and the scheduler on the host, and uses Docker
  Compose only for MariaDB, Redis, and Mailpit.
- `compose.yaml` runs the application stack in containers with FrankenPHP.

For the local process runner, start the dependency services with:

```bash
docker compose up --detach --wait mariadb redis mailpit
```

Use `docker compose down` to stop those services. Add `--volumes` only when you also want to delete local database,
Redis, and Mailpit data.

## PHP
On Ubuntu:

`sudo apt install php php-{bcmath,curl,intl,mbstring,mysql,redis,xml,zip}`

## Composer
See https://getcomposer.org/download/

## Node
Use the Node version from the Nix dev shell. Without Nix, install Node 22 or newer through your operating system,
package manager, or another local Node version manager.

## Database

### MariaDB
See https://mariadb.com/kb/en/mariadb-package-repository-setup-and-usage/

You only need a native MariaDB installation if you are not using the Docker Compose services above.

Install MariaDB on Ubuntu:

- `sudo apt install mariadb-server`

Create the database:

1. `sudo mariadb`
2. `CREATE DATABASE roomz;`
3. `CREATE USER 'roomz'@'127.0.0.1' IDENTIFIED BY 'keepMeSe5r3t!';`
4. `GRANT ALL PRIVILEGES ON roomz.* TO 'roomz'@'127.0.0.1';`
5. `FLUSH PRIVILEGES;`
6. `exit`

These values match `.env.example`, where Laravel connects to MariaDB through `DB_HOST=127.0.0.1`.

# Installation

1. Clone the git repository
2. Install php dependencies
    - `composer install`
3. Install node dependencies
    - `npm install`
4. Create a dotenv file
    - `cp .env.example .env`
5. Adjust the dotenv file
6. Create an app key
    - `php artisan key:generate`
7. Create database tables
    - `php artisan migrate`
8. Create necessary data
    - `php artisan db:seed`
9. Create a routes file
    - `php artisan ziggy:generate`
10. Compile the frontend
    - Development: `npm run dev`
    - Production: `npm run build`
11. Start the websockets server
    - `php artisan reverb:start`
12. Set up a reverse proxy (optional)
13. Set up Redis, or change `.env` to non-Redis cache, session, and queue drivers
14. Run queue workers
    - See https://laravel.com/docs/11.x/queues#running-the-queue-worker
15. Run the scheduler
    - See https://laravel.com/docs/11.x/scheduling#running-the-scheduler

# Local Process Runner
This repository includes `process-compose.yaml` for local development. It starts MariaDB, Redis, and Mailpit through
Docker Compose, then runs the Laravel server, Vite, Reverb, the queue worker, and the scheduler on the host.

Enter the dev shell first, unless direnv has already loaded it for you:

```bash
nix develop
```

For this hybrid setup, keep the Laravel application host as `localhost` and point service clients to Docker's
host-exposed ports in `.env`:

```dotenv
APP_HOST=localhost
APP_URL=http://localhost:8000
DB_HOST=127.0.0.1
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=6001
IS_TEST_ACCOUNTS_ENABLED=true
DB_SEED_EXAMPLE_INSTITUTION=true
```

Apply these overrides before running `php artisan migrate --seed`; the example data flag is only read during seeding.

On first setup, install dependencies and prepare the app:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
docker compose up --detach --wait mariadb redis mailpit
php artisan migrate --seed
```

Then start the local stack:

```bash
process-compose up
```

The Nix shell and direnv both set `PC_CONFIG_FILES` to `process-compose.yaml` because the repository also has Docker's
`compose.yaml`. Without Nix, install `process-compose` separately and run:

```bash
process-compose -f process-compose.yaml up
```

Open the app at `http://localhost:8000`. Do not use `http://127.0.0.1:8000` unless you also change `APP_HOST`, because
the `TrustHosts` middleware derives trusted hosts from `APP_URL`.

# FrankenPHP Compose Stack
Use `compose.yaml` when you want the application to run in containers instead of on the host. The `frankenphp` service
builds the final `frankenphp` target from the `Dockerfile`, serves `public/` through Caddy and FrankenPHP, proxies
websocket requests under `/app/*` to the `reverb` service, and exposes Mailpit under `/mailpit`.

The container build and runtime both read `.env` as a Docker secret. Generate `APP_KEY` on the host before building,
because the secret is mounted read-only inside the containers:

```bash
cp .env.example .env
composer install
php artisan key:generate
```

If you are using the local process runner setup above, reuse the `.env` you already prepared. Then build the image,
start the service containers, run migrations, and start the full stack:

```bash
docker compose build
docker compose up --detach --wait mariadb redis mailpit
docker compose run --rm frankenphp php artisan migrate --seed
docker compose up --detach
```

Open the app at the configured `APP_URL`. With the default `.env.example`, that is `http://localhost:8000`. Mailpit is
available at `/mailpit` on the same host.

# Test User Accounts
Set `IS_TEST_ACCOUNTS_ENABLED=true` in `.env` to enable the local test accounts. Their default credentials are:

- Regular user 1:
  - Username: test1
  - Password: test1
- Regular user 2:
  - Username: test2
  - Password: test2
- Admin user:
  - Username: admin
  - Password: admin

**IMPORTANT**: You **must** change these passwords in production.

# Reverse Proxy

When running on a production server, you probably want to proxy the websocket connections (since you may not have full
control over the firewall). The sample configs assume the app is deployed under `/srv/git/roomz/` and Reverb listens
on `127.0.0.1:6001`.

## Apache
Module `rewrite` must be enabled.
Module `proxy_wstunnel` must be enabled.

Use `deployment/apache.conf` as the starting point.

## Nginx
Use `deployment/nginx.conf` as the starting point. It assumes PHP-FPM is running locally; adjust the PHP-FPM socket
path if your distribution uses a different version or service name.

# Systemd
Sample systemd units for the app-managed production services are in `deployment/systemd/`:

- `roomz-reverb.service` runs the Laravel Reverb websocket server.
- `roomz-queue.service` runs the Laravel queue worker.
- `roomz-scheduler.service` runs the Laravel scheduler.

The units assume:

- The repository is deployed to `/srv/git/roomz`.
- PHP is available in the systemd service `PATH`; otherwise replace `/usr/bin/env php` with the absolute PHP path.
- The services run as `www-data:www-data`.
- MariaDB and Redis use one of the common distro service names referenced in the `After=` lines.

Adjust those values before installing the units. Then copy and enable them:

```bash
sudo cp deployment/systemd/*.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now roomz-reverb.service roomz-queue.service roomz-scheduler.service
```

# Redeployment

The GitLab deploy jobs SSH into the target host, check out the selected commit, and run `scripts/deploy.sh` with the
environment-specific build prefix. For the current deployment workflow, redeploy by running:

```bash
scripts/deploy.sh "$BUILD_PREFIX"
```

The script installs PHP and Node dependencies, warms Laravel caches with `php artisan optimize --except=routes`,
generates Ziggy routes, runs forced migrations with seeders, and builds the frontend with
`npm run build -- --base="$BUILD_PREFIX/build"`.

If you need to run the steps manually, keep the same order:

```bash
composer install
npm clean-install
php artisan optimize --except=routes
php artisan ziggy:generate
php artisan migrate --force --seed
npm run build -- --base="$BUILD_PREFIX/build"
```

After deploying new code, restart the PHP application, Reverb, queue worker, and schedule worker via your process
supervisor so long-running processes use the new code and cached configuration. With the sample systemd units, restart
the app-managed services with:

```bash
sudo systemctl restart roomz-reverb.service roomz-queue.service roomz-scheduler.service
```
