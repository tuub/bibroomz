# Roomz

# Installation

1. Clone the git repo
2. Run `composer install`
3. Run `npm install`
4. Create config file: `cp .env.example .env`
5. Create database
6. Edit config file, especially sections "CONNECTION" and "DATABASE"
7. Create app key via `php artisan key:generate`
8. Create database tables with `php artisan migrate`
9. Create necessary data and some samples with `php artisan db:seed`
10. Create routes file via `php artisan ziggy:generate`
11. Add webserver config (see below)
12. Setup websockets (see below)
13. Compile app:
    - Development: `npm run dev`
    - Production: `npm run prod`

# Deployment
We assume the app is deployed under `/srv/git/roomz/`.

# User accounts
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

## Websockets
We use Soketi for the websockets connection.

* Local installation: `npm install` (it's in the `package.json`)
* Global Installation: `npm install @soketi/soketi -g`

### IMPORTANT
The config file `soketi.json` must always reflect the same values as the `.env` file. 

The pm2 config file `soketi-pm2.json` must always reflect the correct values:

````
{
    "apps": [
        {
           "name": "soketi",
           
           // CHECK PATH HERE
           "cwd": "/srv/git/roomz",

           // FOR GLOBAL INSTALLATION: REMOVE THE "npx" AT THE BEGINNING
           "script": "npx soketi start --config=soketi.json",
           
           "env": {
              "NODE_ENV": "production"
           }
        }
    ]
}
````

### Run the websockets server
* Manually: `npx soketi start --config=/srv/git/roomz/soketi.json`
* Automatically via PM2:
  * Add soketi service to pm2: `pm2 start soketi-pm2.json && pm2 save`
  * Start/Stop/Restart: `pm2 (start|stop|restart) soketi`
  * Check status: `pm2 status soketi`

### Proxy

When using on a production server, you probably want to proxy the websockets connections 
(since you may not have full control over the firewall). Here's how to do it.

#### Apache2
As learned in https://stackoverflow.com/a/43592531

Module `rewrite` must be enabled.

We assume that there is a symlink set from `/srv/git/roomz/public` to `/srv/www/roomz`.

````
<IfModule mod_ssl.c>
    <VirtualHost _default_:443>
        ServerAdmin admin@example.org
        ServerName roomz.example.org

        DocumentRoot "/srv/www/roomz/"

        <Directory "/srv/www/roomz">
            Options -Indexes +FollowSymLinks +MultiViews
            AllowOverride All
            Require all granted    
        </Directory>

        # ---- Important for websockets! ------
        RewriteEngine On
        RewriteCond %{HTTP:Upgrade} =websocket [NC]
        RewriteRule /(.*)    ws://localhost:6001/$1 [P,L]
        
        [...]

        </VirtualHost>
</IfModule>
````

#### nginx
TODO
