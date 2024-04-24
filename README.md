# BibRoomz

# Prerequisites

You need the following software installed to run this project:

- PHP (>= 8.2) with the following extensions
    - bcmath
    - curl
    - mbstring
    - mysql
    - redis (optional)
    - xml
- Composer
- Node (tested with 18 and 20)

In addition, a database is required. Currently, only MariaDB is supported (others have not been tested).

## PHP
On Ubuntu:

`sudo apt install php php-{curl,xml,mysql,mbstring,bcmath}`

## Composer
See https://getcomposer.org/download/

## Node
With Volta:

1. `curl https://get.volta.sh | bash`
2. `volta install node@lts` 

## Database

### MariaDB
See https://mariadb.com/kb/en/mariadb-package-repository-setup-and-usage/

Install MariaDB on Ubuntu:

- `sudo apt install mariadb-server`

Create the database:

1. `sudo mariadb`
2. `CREATE DATABASE roomz;`
3. `GRANT ALL PRIVILEGES ON roomz.* TO roomz@localhost identified by 's*3*c*r*3*t';`
4. `FLUSH PRIVILEGES;`
5. `exit`

# Installation

1. Clone the git repository
2. Install php dependencies
    - `composer install`
3. Install node dependencies
    - `npm install`
4. Create a dotenv file
    - `cp .env.example .env`
5. Adjust the dotenv file
5. Create an app key
    - `php artisan key:generate`
6. Create database tables
    - `php artisan migrate`
7. Create necessary data
    - `php artisan db:seed`
8. Create a routes file
    - `php artisan ziggy:generate`
9. Compile the frontend
    - Development: `npm run dev`
    - Production: `npm run build`
9. Start the websockets server
    - `php artisan reverb:start`
10. Set up a reverse proxy (optional)
11. Set up redis (optional)
12. Run queue workers
    - See https://laravel.com/docs/11.x/queues#running-the-queue-worker
13. Run the scheduler
    - See https://laravel.com/docs/11.x/scheduling#running-the-scheduler

# Test User Accounts
Three test accounts exist which you can configure in the `.env` file.
Initially these accounts are:

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

When using on a production server, you probably want to proxy the websockets connections (since you may not have full
control over the firewall). Here's how to do it.

## Apache
See https://stackoverflow.com/a/43592531

Module `rewrite` must be enabled.
Module `proxy_wstunnel` must be enabled.

We assume that the app is deployed under `/srv/git/roomz/`.

````
<IfModule mod_ssl.c>
    <VirtualHost _default_:443>
        ServerAdmin admin@example.org
        ServerName roomz.example.org

        DocumentRoot "/srv/git/roomz/public"

        <Directory "/srv/git/roomz/public">
            Options -Indexes +FollowSymLinks +MultiViews
            AllowOverride All
            Require all granted
        </Directory>

        # ---- Important for websockets! ------
        RewriteEngine On
        RewriteCond %{HTTP:Upgrade} =websocket [NC]
        RewriteRule /(.*) ws://localhost:6001/$1 [P,L]

        [...]
    </VirtualHost>
</IfModule>
````

# Redeployment

Recompile the frontend:
- `npm run build`

If routes have changed:
- `php artisan route:clear`
- `php artisan route:cache`
- `php artisan ziggy:generate`
- `npm run build`

If events/listeners have changed:
- `php artisan event:clear`
- `php artisan event:cache`

If environment variables or config files have changed:
- `php artisan config:clear`
- `php artisan config:cache`

If blade templates have changed:
- `php artisan view:clear`
- `php artisan view:cache`

If database migrations have been added:
- `php artisan migrate`

Restart the schedule worker via your process supervisor.
