#!/usr/bin/env bash

## Determine the absolute path of the directory with the file
## usage: absdirname <file-path>
function absdirname() {
  pushd $(dirname $0) >> /dev/null
    pwd
  popd >> /dev/null
}

SCRDIR=$(absdirname "$0")
PRJDIR=$(dirname "$SCRDIR")
OUTFILE="$PRJDIR/bin/civix.phar"
set -ex

## Box's temp file convention is not multi-user aware. Prone to permission error when second user tries to write.
export TMPDIR="/tmp/box-$USER"
if [ ! -d "$TMPDIR" ]; then mkdir "$TMPDIR"  ; fi

pushd "$PRJDIR" >> /dev/null
  composer install --prefer-dist --no-progress --no-suggest --no-dev
  nix-shell --run 'box compile -v'
  php scripts/check-phar.php "$OUTFILE"
popd >> /dev/null
