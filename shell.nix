/**
 * This shell is suitable for compiling PHAR executables.... and not much else.
 *
 * Ex: `nix-shell --run ./scripts/build.sh`
 */

{ pkgs ? import <nixpkgs> {} }:

let

  buildkit = (import ./nix/buildkit.nix) { inherit pkgs; };

in

  pkgs.mkShell {
    nativeBuildInputs = buildkit.profiles.base ++ [

      (buildkit.pins.v2305.php82.buildEnv {
        extraConfig = ''
          memory_limit=-1
        '';
      })

      buildkit.pkgs.box
      buildkit.pkgs.composer
      buildkit.pkgs.pogo
      buildkit.pkgs.phpunit8
      buildkit.pkgs.phpunit9

      pkgs.bash-completion
    ];
    shellHook = ''
      source ${pkgs.bash-completion}/etc/profile.d/bash_completion.sh
    '';
  }
