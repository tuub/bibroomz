{
  inputs.nixpkgs.url = "github:nixos/nixpkgs/nixos-unstable";

  outputs =
    { nixpkgs, ... }:
    let
      systems = [
        "x86_64-linux"
        "aarch64-linux"
        "x86_64-darwin"
        "aarch64-darwin"
      ];
      forEachSystem = nixpkgs.lib.genAttrs systems;
    in
    {
      devShells = forEachSystem (
        system:
        let
          pkgs = import nixpkgs { inherit system; };
          php = pkgs.php83.buildEnv {
            extensions =
              { all, enabled }:
              enabled
              ++ [
                all.pcov
                all.redis
                all.spx
              ];
            extraConfig = ''
              memory_limit=-1

              pcov.enabled=1
              pcov.directory=app/

              spx.http_enabled=1
              spx.http_key=dev
              spx.http_ip_whitelist=127.0.0.1,::1
              spx.http_ui_assets_dir=${pkgs.php83.extensions.spx}/share/misc/php-spx/assets/web-ui
            '';
          };
        in
        {
          default = pkgs.mkShell {
            packages = [
              php
              php.packages.composer
              pkgs.nodejs_24
              pkgs.process-compose
            ];
            shellHook = ''
              export PATH="$PWD/vendor/bin:$PWD/node_modules/.bin:$PATH"
              export PC_CONFIG_FILES="$PWD/process-compose.yaml"
              export PLAYWRIGHT_BROWSERS_PATH="${pkgs.playwright.browsers}"
              export FONTCONFIG_FILE="${pkgs.makeFontsConf { fontDirectories = [ pkgs.dejavu_fonts ]; }}"
            '';
          };
        }
      );
    };
}
