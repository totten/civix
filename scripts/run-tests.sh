#!/bin/bash
set -e

## Run PHPUnit tests. This is a small wrapper for `phpunit8` which does some setup for the E2E enviroment.

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
[ ! -d "$CIVIX_WORKSPACE" ] && mkdir -p "$CIVIX_WORKSPACE" || echo ''
(cd "$CIVIX_WORKSPACE" && civibuild restore)

phpunit8 "$@"
