{ pkgs ? import <nixpkgs> {} }:

## Get civicrm-buildkit from github.
## Based on "master" branch circa 2026-01-30 04:33 UTC
import (pkgs.fetchzip {
  url = "https://github.com/civicrm/civicrm-buildkit/archive/c1dbe963785e8205307a58ecb1dc826f9f8abb25.tar.gz";
  sha256 = "12csnfk6246msiadqkdjlxk943xb0bwnrhksnvbhw5mm8a2gy4ym";
})

## Get a local copy of civicrm-buildkit. (Useful for developing patches.)
# import ((builtins.getEnv "HOME") + "/buildkit/default.nix")
# import ((builtins.getEnv "HOME") + "/bknix/default.nix")
