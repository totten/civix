/**
 * This shell is suitable for compiling civix.phar.... and not much else.
 *
 * Ex: `nix-shell --run ./scripts/build.sh`
 */

{ pkgs ? import <nixpkgs> {} }:

let

  buildkit = import (pkgs.fetchFromGitHub {
    owner = "totten";
    repo = "civicrm-buildkit";
    rev = "153371e9bdcb22392b878cca545df0888fb61925";
    sha256 = "sha256-rdwmA4uqIqfqXu2f+ewVH0Gs/BzcB13p8oRbbTdUsAs=";
  });

  ## If you're trying to patch buildkit at the sametime, then use a local copy:
  #buildkit = import ((builtins.getEnv "HOME") + "/bknix/default.nix");

in

  pkgs.mkShell {
    nativeBuildInputs = buildkit.profiles.base ++ [

      (buildkit.pins.v2305.php81.buildEnv {
        extraConfig = ''
          memory_limit=-1
        '';
      })

      buildkit.pkgs.box
      buildkit.pkgs.composer
      buildkit.pkgs.pogo
      buildkit.pkgs.phpunit8

      pkgs.bash-completion
    ];
    shellHook = ''
      source ${pkgs.bash-completion}/etc/profile.d/bash_completion.sh
    '';
  }
