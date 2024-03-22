#!/bin/bash

## Do a trial run -- by upgrading some of the snapshots.
## This is a wrapper for PHPUnit that twiddles some of the environment.

if [ -z "$1" ]; then
  echo "Try out the upgrade procedure on a set of snapshots"
  echo ""
  echo "usage: $0 <snapshot-regex>"
  echo "example: $0 '/v16/'"
  echo "example: $0 '/-qf/'"
  echo "example: $0 '/v22.*-entity/'"
  echo
  echo "Snapshots are read from 'tests/snapshots/*/original.zip'"
  echo "Upgrades (with logs and diffs) are written to 'tests/snapshots/*/upgrade'"
  exit 1
fi

echo "Snapshots are read from: $PWD/tests/snapshots/*/original.zip"
echo "Upgrades will be written to: $PWD/tests/snapshots/*/upgrade"

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
export SNAPSHOT_SAVE=1
export SNAPSHOT_FILTER="$1"
phpunit9 tests/e2e/SnapshotUpgradeTest.php --debug
