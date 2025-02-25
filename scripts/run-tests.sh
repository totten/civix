#!/bin/bash
set -e

## Run PHPUnit tests. This is a small wrapper for `phpunit` which does some setup for the E2E enviroment.
##
## You may optionally specify whether to run E2E tests using the raw civix source or compiled civix.phar.
##
## usage: ./scripts/run-tests.sh [--civix-src|--civix-phar] [phpunit-args...]

################################################
CIVIX_BUILD_TYPE=src
case "$1" in
  --civix-src|--civix-phar) CIVIX_BUILD_TYPE="$1" ; shift ; ;;
esac

################################################
## Didn't set a workspace? Educated guess...
if [ -z "$CIVIX_WORKSPACE" -a -d "$CIVIBUILD_HOME/dmaster/web/sites/all/modules/civicrm" ]; then
  export CIVIX_WORKSPACE="$CIVIBUILD_HOME/dmaster/web/sites/all/modules/civicrm/ext/civixtest"
  echo "Inferred CIVIX_WORKSPACE=$CIVIX_WORKSPACE"
fi
if [ -z "$CIVIX_WORKSPACE" ]; then
  echo "Missing env var: CIVIX_WORKSPACE"
  exit 1
fi

################################################
if [ "$CIVIX_BUILD_TYPE" = "--civix-phar" ]; then
  if [ ! -f "box.json" -o ! -f "scripts/build.sh" ]; then
    echo "Must call from civix root dir"
    exit 1
  fi
  ./scripts/build.sh
  CIVIX_TEST_BINARY="$PWD"/bin/civix.phar
  export CIVIX_TEST_BINARY
else
  unset CIVIX_TEST_BINARY
fi

################################################
[ ! -d "$CIVIX_WORKSPACE" ] && mkdir -p "$CIVIX_WORKSPACE" || echo ''
(cd "$CIVIX_WORKSPACE" && XDEBUG_MODE=off civibuild restore)

phpunit9 "$@"
