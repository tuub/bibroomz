{
  config,
  pkgs,
  lib,
  ...
}:

let
  mysqlDatabase = config.env.DB_DATABASE;
  mysqlUsername = config.env.DB_USERNAME;
  mysqlPassword = config.env.DB_PASSWORD;

  redisPassword = config.env.REDIS_PASSWORD;
in
{
  env =
    let
      mkFallback = lib.mkOverride 1500;
    in
    {
      APP_DEBUG = mkFallback "true";
      APP_ENV = mkFallback "local";
      APP_HOST = mkFallback "localhost";
      APP_PORT = mkFallback "8000";
      APP_SCHEME = mkFallback "http";
      APP_URL = mkFallback "${config.env.APP_SCHEME}://${config.env.APP_HOST}:${config.env.APP_PORT}";
      CACHE_DRIVER = mkFallback "redis";
      DB_DATABASE = mkFallback "bibroomz";
      DB_PASSWORD = mkFallback "bibroomz";
      DB_USERNAME = mkFallback "bibroomz";
      MAIL_HOST = mkFallback "localhost";
      MAIL_MAILER = mkFallback "mailpit";
      QUEUE_CONNECTION = mkFallback "redis";
      REDIS_PASSWORD = mkFallback "";
      REVERB_HOST = mkFallback "localhost";
      REVERB_PORT = mkFallback "6001";
      REVERB_SCHEME = mkFallback "http";
      REVERB_SERVER_HOST = mkFallback "127.0.0.1";
      REVERB_SERVER_PORT = mkFallback "6001";
      SESSION_DRIVER = mkFallback "redis";
      VITE_API_URL = mkFallback "${config.env.APP_URL}";
      VITE_REVERB_HOST = mkFallback "${config.env.REVERB_HOST}";
      VITE_REVERB_PORT = mkFallback "${config.env.REVERB_PORT}";
      VITE_REVERB_SCHEME = mkFallback "${config.env.REVERB_SCHEME}";
    };

  cachix.enable = false;
  dotenv.enable = false;

  enterShell = lib.mkForce "";
  tasks = lib.mkDefault { };

  languages = {
    javascript = {
      enable = true;
      npm.enable = true;
      package = pkgs.nodejs_24;
    };

    php = {
      enable = true;
      package = pkgs.php84.buildEnv { extensions = { all, enabled }: enabled ++ [ all.redis ]; };
    };
  };

  processes = {
    backend.exec = "php artisan serve";
    frontend.exec = "npm run dev";

    queue-worker = {
      exec = "php artisan queue:listen";
      process-compose.availability.restart = "always";
    };

    schedule-worker.exec = "php artisan schedule:work";
    reverb.exec = "php artisan reverb:start --debug";
    ziggy.exec = "php artisan ziggy:generate";
  };

  services = {
    mailpit.enable = true;

    mysql = {
      enable = true;
      ensureUsers = [
        {
          name = mysqlUsername;
          password = mysqlPassword;
          ensurePermissions = {
            "${mysqlDatabase}.*" = "ALL PRIVILEGES";
          };
        }
      ];
      initialDatabases = [ { name = mysqlDatabase; } ];
    };

    redis = {
      enable = true;
      extraConfig = if redisPassword == "" then "" else "requirepass ${redisPassword}";
    };
  };
}
