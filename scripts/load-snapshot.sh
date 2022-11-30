#!/usr/bin/env bash

## Load an snapshot, i.e.
## - Clean the test site.
## - Extract the snapshot into the test site.
## - Install the extension

################################################
function usage() {
  echo "$0 [-r] [-x] [-g] [-e] [-u] [-s <source>] "
  echo
  echo "Data:"
  echo "-s     (Source)   Specify which snapshot file to load"
  echo
  echo "Actions:"
  echo "-r     (Reset)    Reset Civi DB"
  echo "-x     (Extract)  Extract the zip file to a temporary folder"
  echo "-g     (Git)      Add temporary folder to a temporary git repo"
  echo "-e     (Enable)   Enable the extension in CiviCRM"
  echo "-u     (Upgrade)   Run civix upgrades"
  echo
  echo "Example:"
  echo "  $0 -rxge -s tests/snapshots/org.example.civixsnapshot-v19.11.0-kitchensink/"
  echo
  echo "NOTE: Actions are executed in requested order. So these two are slightly different:"
  echo "  $0 -rxgeu    (restore => extract => git => enable => upgrade)"
  echo "  $0 -rxgue    (restore => extract => git => upgrade => enable)"
}

################################################
EXMODULE=${EXMODULE:-org.example.civixsnapshot}
#SNAPSHOT="$1"
#shift
SNAPSHOT=
TASKS=
while getopts "hs:rxgeu" opt; do
  case $opt in
    h) usage ; exit ; ;;
    s) SNAPSHOT="$OPTARG" ; ;;
    r) TASKS="$TASKS restore" ; ;;
    x) TASKS="$TASKS extract" ; ;;
    g) TASKS="$TASKS git" ; ;;
    e) TASKS="$TASKS enable" ; ;;
    u) TASKS="$TASKS upgrade" ; ;;
  esac
done

echo "SNAPSHOT=[$SNAPSHOT] TASKS=[$TASKS]"
if [  -z "$TASKS" -o -z "$SNAPSHOT" ]; then
  usage 2>&1
  exit 1
fi

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
## Main

if [ -n "$SNAPSHOT" -a -e "$SNAPSHOT/original.zip" ]; then
  SNAPSHOT="$SNAPSHOT/original.zip"
fi

if [ -z "$SNAPSHOT" -o ! -e "$SNAPSHOT" ]; then
  echo "Usage: $0 <snapshot-file>"
  exit 1
fi

set -ex

## Convert to absolute path
ZIP=$(readlink -f "$SNAPSHOT")
if [ ! -f "$ZIP" ]; then
  echo "Error resolving $SNAPSHOT to file"
  exit 1
fi

## Go extract it
pushd "$CIVIX_WORKSPACE"
  for TASK in $TASKS ; do
    case "$TASK" in
      restore) civibuild restore ; ;;
      extract) [ -d "$EXMODULE" ] && rm -rf "$EXMODULE" ; unzip "$ZIP" ; ;;
      git)
        pushd "$EXMODULE"
          git init
          git add .
          git commit -m skeleton
        popd
        ;;
      enable) cv en "$EXMODULE" ; ;;
      upgrade) echo "FIXME: Need CIVIX var" ; ;;
      # upgrade) pushd "$EXMODULE" && $CIVIX upgrade && popd ; ;;
      *) echo "Unrecognized task: $TASK" ; exit 1; ;;
    esac
  done
popd

echo "Output: $CIVIX_WORKSPACE/$EXMODULE"
