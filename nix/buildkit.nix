{ pkgs ? import <nixpkgs> {} }:

## Get civicrm-buildkit from github.
## Based on "master" branch circa 2023-08-22 10:32 UTC
import (pkgs.fetchzip {
  url = "https://github.com/civicrm/civicrm-buildkit/archive/04b338a52bbf0bdc21edafe564b875138c1db12c.tar.gz";
  sha256 = "1r6m830lyv6xf0yxz392izhwlnbahhc7s4r71riyqryzkr6psfyd";
})

## Get a local copy of civicrm-buildkit. (Useful for developing patches.)
# import ((builtins.getEnv "HOME") + "/buildkit/default.nix")
# import ((builtins.getEnv "HOME") + "/bknix/default.nix")
