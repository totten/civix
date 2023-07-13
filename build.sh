#!/usr/bin/env bash

## Determine the absolute path of the directory with the file
## usage: absdirname <file-path>
function absdirname() {
  pushd $(dirname $0) >> /dev/null
    pwd
  popd >> /dev/null
}

PRJDIR=$(absdirname "$0")
OUTFILE="$PRJDIR/bin/civix.phar"
set -ex

BOX_VERSION=4.3.8
BOX_URL="https://github.com/box-project/box/releases/download/${BOX_VERSION}/box.phar"
BOX_DIR="$PRJDIR/extern/box-$BOX_VERSION"
BOX_BIN="$BOX_DIR/box"
[ ! -f "$BOX_BIN" ] && ( mkdir -p "$BOX_DIR" ; curl -L "$BOX_URL" -o "$BOX_BIN" )

## Box's temp file convention is not multi-user aware. Prone to permission error when second user tries to write.
export TMPDIR="/tmp/box-$USER"
if [ ! -d "$TMPDIR" ]; then mkdir "$TMPDIR"  ; fi

pushd "$PRJDIR" >> /dev/null
  composer install --prefer-dist --no-progress --no-suggest --no-dev
  BOX_ALLOW_XDEBUG=1 php -d phar.readonly=0 "$BOX_BIN" compile -v

  ## Box needs the PHP INI to specify `phar.readonly=0`. We've being doing this with `php -d` since forever.
  ## It appears that newer versions of Box try to do this automatically (yah!), but the implementation is buggy (arg!).
  ## Setting BOX_ALLOW_XDEBUG=1 opts-out of the buggy implementation.

  ## The specific bug - it shows a bazillion warnings like this (observed on bknix with php74 or php80)
  ##     Ex: `Warning: Module "memcached" is already loaded in Unknown on line 0`
  ## In some cases, these warnings appear as errors. (I suspect the extra output provokes the error.)
  ##     Ex: When `box compile` calls down to `composer dumpautoload`, esp on php80

  ## How to opt-out of the buggy implementation?  One needs to see that Box has borrowed half of the implementation from
  ## `composer/xdebug-handler`.  (Both have a need to manipulate PHP INI.) The flag `BOX_ALLOW_XDEBUG` is defined by their
  ## upstream.  Setting the flag doesn't actually configure xdebug -- rather, it disables PHP INI automanipulations, so that you
  ## are _allowed_ to set PHP INI options (`xdebug.*`, `phar.*`, etc) on your own.

  php scripts/check-phar.php "$OUTFILE"

popd >> /dev/null
