{
  inputs = {
    devenv = {
      url = "github:cachix/devenv";
      inputs.nixpkgs.follows = "nixpkgs";
    };

    nixpkgs.url = "github:nixos/nixpkgs/nixos-unstable";
    systems.url = "github:nix-systems/default";
  };

  outputs =
    { self, ... }@inputs:
    let
      forEachSystem = inputs.nixpkgs.lib.genAttrs (import inputs.systems);
    in
    {
      inherit (inputs) nixpkgs;

      packages = forEachSystem (system: {
        devenv-up = self.devShells.${system}.default.config.procfileScript;
      });

      devShells = forEachSystem (
        system:
        let
          pkgs = import inputs.nixpkgs { inherit system; };
        in
        {
          default = inputs.devenv.lib.mkShell {
            inherit inputs pkgs;

            modules = [ ./devenv.nix ];
          };
        }
      );
    };
}
