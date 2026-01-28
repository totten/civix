{ pkgs ? import <nixpkgs> {} }:

## Get civicrm-buildkit from github.
## Based on "master" branch circa 2026-01-27 23:56 UTC
import (pkgs.fetchzip {
  url = "https://github.com/civicrm/civicrm-buildkit/archive/ad82671e7468491131e6db19440b26b6b35d8d86.tar.gz";
  sha256 = "0igyhcd18agy90p4h9gmzx0q8rmigab0hxjc4z7yp6kkc663byya";
})

## Get a local copy of civicrm-buildkit. (Useful for developing patches.)
# import ((builtins.getEnv "HOME") + "/buildkit/default.nix")
# import ((builtins.getEnv "HOME") + "/bknix/default.nix")
