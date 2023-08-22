#!/usr/bin/env bash
{ # https://stackoverflow.com/a/21100710

  ## Re-generate the buildkit.nix file - with the current 'master' branch.

  set -e

  if [ ! -f "nix/buildkit.nix" ]; then
    echo >&2 "Must run in project root"
    exit 1
  fi

  now=$( date -u '+%Y-%m-%d %H:%M %Z' )
  commit=$( git ls-remote https://github.com/civicrm/civicrm-buildkit.git | awk '/refs\/heads\/master$/ { print $1 }' )
  url="https://github.com/civicrm/civicrm-buildkit/archive/${commit}.tar.gz"
  hash=$( nix-prefetch-url "$url" --type sha256 --unpack )

  function render_file() {
    echo "{ pkgs ? import <nixpkgs> {} }:"
    echo ""
    echo "## Get civicrm-buildkit from github."
    echo "## Based on \"master\" branch circa $now"
    echo "import (pkgs.fetchzip {"
    echo "  url = \"$url\";"
    echo "  sha256 = \"$hash\";"
    echo "})"
    echo
    echo "## Get a local copy of civicrm-buildkit. (Useful for developing patches.)"
    echo "# import ((builtins.getEnv \"HOME\") + \"/buildkit/default.nix\")"
    echo "# import ((builtins.getEnv \"HOME\") + \"/bknix/default.nix\")"
  }
  render_file > nix/buildkit.nix

  exit
}
