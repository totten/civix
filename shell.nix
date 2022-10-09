/**
 * This shell is suitable for compiling civix.phar.... and not much else.
 *
 * Ex: `nix-shell --run ./build.sh`
 */
{ pkgs ? import <nixpkgs> {} }:
  pkgs.mkShell {
    # nativeBuildInputs is usually what you want -- tools you need to run
    nativeBuildInputs = [ pkgs.php74 pkgs.php74Packages.composer ];
}
