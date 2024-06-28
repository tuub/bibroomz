{ config, pkgs, lib, ... }:

let
  caddyUrl = config.env.APP_URL;

  mysqlDatabase = config.env.DB_DATABASE;
  mysqlUsername = config.env.DB_USERNAME;
  mysqlPassword = config.env.DB_PASSWORD;

  redisPassword = config.env.REDIS_PASSWORD;

  reverbHost = config.env.REVERB_SERVER_HOST;
  reverbPort = config.env.REVERB_SERVER_PORT;
in {
  env = let mkFallback = lib.mkOverride 1500;
  in {
    APP_ENV = lib.mkForce "devenv";
    APP_HOST = mkFallback "localhost";
    APP_PORT = mkFallback "8000";
    APP_SCHEME = mkFallback "http";
    APP_URL = mkFallback "${config.env.APP_SCHEME}://${config.env.APP_HOST}:${config.env.APP_PORT}";
    DB_DATABASE = mkFallback "bibroomz";
    DB_PASSWORD = mkFallback "bibroomz";
    DB_USERNAME = mkFallback "bibroomz";
    MAIL_HOST = mkFallback "localhost";
    MAIL_MAILER = mkFallback "mailpit";
    REDIS_PASSWORD = mkFallback "";
    REVERB_HOST = mkFallback "localhost";
    REVERB_PORT = mkFallback "6001";
    REVERB_SCHEME = mkFallback "http";
    REVERB_SERVER_HOST = mkFallback "127.0.0.1";
    REVERB_SERVER_PORT = mkFallback "6001";
    VITE_API_URL = mkFallback "${config.env.APP_URL}";
  };

  cachix.enable = false;
  dotenv = {
    enable = true;
    disableHint = true;
    filename = ".env.devenv";
  };

  languages = {
    javascript = {
      enable = true;
      npm.enable = true;
      package = pkgs.nodejs_20;
    };

    php = {
      enable = true;
      package = pkgs.php83.buildEnv { extensions = { all, enabled }: enabled ++ [ all.redis ]; };
      fpm.pools.web.settings = {
        "clear_env" = "no";

        "pm" = "dynamic";
        "pm.max_children" = 10;
        "pm.min_spare_servers" = 1;
        "pm.max_spare_servers" = 5;
        "pm.start_servers" = 2;
      };
    };
  };

  process.before = "installScript";

  processes = {
    build.exec = ''
      php artisan ziggy:generate
      npm run build -- --mode devenv
    '';

    queue-worker = {
      exec = "php artisan queue:listen";
      process-compose.availability.restart = "always";
    };

    schedule-worker.exec = "php artisan schedule:work";
    reverb.exec = "php artisan reverb:start --debug";
  };

  scripts = {
    installScript.exec = ''
      set -x

      composer install
      npm clean-install --silent
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
      config = lib.mkIf (config.env.APP_SCHEME == "https") ''
        {
          auto_https disable_redirects
          skip_install_trust

          servers {
            listener_wrappers {
              http_redirect
              tls
            }
          }
        }
      '';
      virtualHosts."${caddyUrl}".extraConfig = ''
        root * public
        php_fastcgi unix/${config.languages.php.fpm.pools.web.socket}
        file_server

        reverse_proxy /app/* ${reverbHost}:${reverbPort}

        tls internal
      '';
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
      extraConfig = if redisPassword == "" then "" else "requirepass ${redisPassword}";
    };
  };
}
