{ config, pkgs, ... }:

let
  caddyPort = "8000";
  websocketPort = "6001";

  mysqlDatabase = "roomz";
  mysqlUsername = "roomz";
  mysqlPassword = "roomz";

  redisPassword = "roomz";
in {
  cachix.enable = false;
  dotenv.disableHint = true;

  languages = {
    javascript = {
      enable = true;
      npm.enable = true;
      package = pkgs.nodejs_18;
    };

    php = {
      enable = true;
      package = pkgs.php83.buildEnv { extensions = { all, enabled }: enabled ++ [ all.redis ]; };
      fpm.pools.web = {
        settings = {
          "pm" = "dynamic";
          "pm.max_children" = 75;
          "pm.min_spare_servers" = 5;
          "pm.max_spare_servers" = 20;
          "pm.max_requests" = 500;
        };
      };
    };
  };

  process.before = "installScript";

  processes = {
    build.exec = ''
      php artisan ziggy:generate
      npm run build
    '';

    queue-worker.exec = "php artisan queue:listen";
    schedule-worker.exec = "php artisan schedule:work";

    reverb.exec = "php artisan reverb:start --debug";
  };

  scripts = {
    installScript.exec = ''
      set -x

      composer install --quiet
      npm install --no-progress --silent

      if [ ! -e .env ]; then
        cp .env.example .env

        sed -i 's/^APP_PORT=.*/APP_PORT=${caddyPort}/' .env
        sed -i 's/^DB_DATABASE=.*/DB_DATABASE=${mysqlDatabase}/' .env
        sed -i 's/^DB_USERNAME=.*/DB_USERNAME=${mysqlUsername}/' .env
        sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=${mysqlPassword}/' .env
        sed -i 's/^REDIS_PASSWORD=.*/REDIS_PASSWORD=${redisPassword}/' .env

        php artisan key:generate
      fi
    '';

    setupScript.exec = ''
      set -x

      php artisan migrate
      php artisan db:seed
    '';
  };

  services = {
    caddy = {
      enable = true;
      virtualHosts = {
        ":${caddyPort}" = {
          extraConfig = ''
            root * public
            php_fastcgi unix/${config.languages.php.fpm.pools.web.socket}
            reverse_proxy /app/* :${websocketPort}
            file_server
          '';
        };
      };
    };

    mailpit.enable = true;

    mysql = {
      enable = true;
      ensureUsers = [{
        name = mysqlUsername;
        password = mysqlPassword;
        ensurePermissions = { "${mysqlDatabase}.*" = "ALL PRIVILEGES"; };
      }];
      initialDatabases = [{ name = mysqlDatabase; }];
    };

    redis = {
      enable = true;
      extraConfig = ''
        requirepass ${redisPassword}
      '';
    };
  };
}
