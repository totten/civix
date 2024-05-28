{ pkgs ? import <nixpkgs> {} }:

## Get civicrm-buildkit from github.
## Based on "master" branch circa 2024-02-26 04:31 UTC
import (pkgs.fetchzip {
  url = "https://github.com/civicrm/civicrm-buildkit/archive/d6f6b8dd2d5944c35cd78cb319fef21673214b35.tar.gz";
  sha256 = "02p2yzdfgv66a2zf8i36h6pjfi78wnp92m3klij7fqbfd9mpvi5a";
})

## Get a local copy of civicrm-buildkit. (Useful for developing patches.)
# import ((builtins.getEnv "HOME") + "/buildkit/default.nix")
# import ((builtins.getEnv "HOME") + "/bknix/default.nix")
