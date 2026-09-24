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

      # Everything the devshell and the CI images have in common, resolved
      # once per system. Both consume this, so the image CI runs in cannot
      # drift away from the shell the same commands are developed in.
      environmentFor =
        system:
        let
          pkgs = import nixpkgs { inherit system; };
          nodejs = pkgs.nodejs_24;
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
          inherit pkgs php nodejs;
          fontsConf = pkgs.makeFontsConf { fontDirectories = [ pkgs.dejavu_fonts ]; };
          browsersPath = "${pkgs.playwright.browsers}";
          packages = [
            php
            php.packages.composer
            nodejs
            pkgs.process-compose
            pkgs.util-linux
          ];
        };

      # The CI images. Jobs run their tools straight off PATH rather than
      # through `nix develop`, so the store closure arrives as image layers
      # the runner keeps between jobs instead of a ~284 MiB cache archive
      # every job unpacks into a ~943 MiB store of its own.
      ciImageFor =
        {
          environment,
          browser,
        }:
        let
          inherit (environment) pkgs;
        in
        pkgs.dockerTools.buildLayeredImage {
          name = if browser then "roomz-ci-browser" else "roomz-ci";
          tag = "latest";
          # One layer per store path as far as the budget goes, so a nixpkgs
          # bump re-uploads only the paths that actually moved.
          maxLayers = 120;
          contents = pkgs.buildEnv {
            name = "roomz-ci-root";
            # Binaries reference their own store paths directly, so this tree
            # only has to make the entry points reachable from PATH.
            pathsToLink = [
              "/bin"
              "/etc"
              "/share"
              # `#!/usr/bin/env` shebangs in scripts/, and fakeNss's /var/empty.
              "/usr"
              "/var"
            ];
            paths = environment.packages ++ [
              # The runner drives every job through `sh`, unpacks artifacts,
              # and commitlint reads history, so the shell, the usual
              # userland and git all have to be in the image.
              pkgs.bashInteractive
              pkgs.coreutils
              pkgs.findutils
              pkgs.diffutils
              pkgs.gnugrep
              pkgs.gnused
              pkgs.gawk
              # scripts/BrowserTestRunner.php probes for setsid with it, and
              # a devshell only ever had it because the host PATH did.
              pkgs.which
              pkgs.gnutar
              pkgs.gzip
              pkgs.xz
              pkgs.zip
              pkgs.unzip
              pkgs.git
              pkgs.openssh
              pkgs.curl
              pkgs.cacert
              # /bin/sh, /usr/bin/env and an /etc/passwd with a root entry:
              # a scratch image has none of them, and composer, npm and git
              # all expect at least one.
              pkgs.dockerTools.binSh
              pkgs.dockerTools.usrBinEnv
              pkgs.dockerTools.fakeNss
            ];
          };
          extraCommands = ''
            mkdir --parents tmp root
            chmod 1777 tmp
          '';
          config = {
            Env = [
              "PATH=/bin"
              "HOME=/root"
              "LANG=C.UTF-8"
              # The container carries no fonts of its own, and headless
              # Chromium renders text at zero size without this.
              "FONTCONFIG_FILE=${environment.fontsConf}"
              "SSL_CERT_FILE=${pkgs.cacert}/etc/ssl/certs/ca-bundle.crt"
              "GIT_SSL_CAINFO=${pkgs.cacert}/etc/ssl/certs/ca-bundle.crt"
            ]
            ++ nixpkgs.lib.optional browser "PLAYWRIGHT_BROWSERS_PATH=${environment.browsersPath}";
          };
        };
    in
    {
      devShells = forEachSystem (
        system:
        let
          environment = environmentFor system;
          default = environment.pkgs.mkShell {
            inherit (environment) packages;
            shellHook = ''
              export PATH="$PWD/vendor/bin:$PWD/node_modules/.bin:$PATH"
              export PC_CONFIG_FILES="$PWD/process-compose.yaml"
              export FONTCONFIG_FILE="${environment.fontsConf}"
            '';
          };
        in
        {
          inherit default;

          # Playwright's browsers pull ~2 GiB into the closure, which every CI
          # job used to download because they hung off the default shell. Only
          # the browser tests launch a browser, so they get their own shell.
          # Run browser tests with `nix develop .#browser`.
          browser = default.overrideAttrs (previous: {
            shellHook = previous.shellHook + ''
              export PLAYWRIGHT_BROWSERS_PATH="${environment.browsersPath}"
            '';
          });
        }
      );

      packages = forEachSystem (
        system:
        let
          environment = environmentFor system;
        in
        {
          # The devshell's Node on its own, for jobs that only need npm and
          # would otherwise download the whole devshell.
          inherit (environment) nodejs;
        }
        // nixpkgs.lib.optionalAttrs environment.pkgs.stdenv.hostPlatform.isLinux {
          ci-image = ciImageFor {
            inherit environment;
            browser = false;
          };

          # Same image plus the Playwright browsers, for the one job that
          # launches one.
          ci-browser-image = ciImageFor {
            inherit environment;
            browser = true;
          };

          # Pushes the two above. Exposed here so the pipeline gets it from
          # this flake's pinned nixpkgs rather than the ambient registry.
          inherit (environment.pkgs) skopeo;
        }
      );
    };
}
