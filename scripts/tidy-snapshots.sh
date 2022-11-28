#!/bin/bash

## Delete ephemeral data like `tests/snapshots/*/upgrade`

################################################
EXMODULE=${EXMODULE:-org.example.civixsnapshot}
SNAPSHOT_DIR="$PWD/tests/snapshots"

################################################

rm -rf "$SNAPSHOT_DIR"/*/upgrade
rm -f "$SNAPSHOT_DIR"/*/upgrade.diff
rm -f "$SNAPSHOT_DIR"/*/upgrade.log
