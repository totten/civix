#!/bin/bash

## Generate a series of example extensions in ./tests/snapshots/{EXTENSION}-{VERSION}-{SCENARIO}.

################################################
## Quick hack for manually testing all commands
CIVIX_BUILD_TYPE=
EXMODULE=${EXMODULE:-org.example.civixsnapshot}
SNAPSHOT_DIR="$PWD/tests/snapshots"
SNAPSHOT_VER=$( git describe --tags )
VERBOSITY=

while [ -n "$1" ]; do
  OPT="$1"
  shift
  case "$OPT" in
    --src|--phar) CIVIX_BUILD_TYPE="$OPT" ; ;;
    --version) SNAPSHOT_VER="$1" ; shift ; ;;
  esac
done


################################################
function show_help() {
  echo "About: Generate a series of example extensions ($EXMODULE) in $SNAPSHOT_DIR."
  echo "Usage: $0 [--phar|--src] [--version VERSION]"
  echo
  echo "Environment:"
  echo "   CIVIX_WORKSPACE: A folder within a live Civi [civibuild] tree where we can put new extensions"
  echo
  echo "Options:"
  echo "  --phar: Create and run a new civix.phar"
  echo "  --src: Directly run the current source tree"
  echo "  --version: Set the version-number on the generated snapshots"
  echo
  echo "Example:"
  echo "  CIVIX_WORKSPACE=\$CIVIBUILD_HOME/dmaster/web/sites/all/modules/civicrm/ext/civixtest bash $0 --src"
}

function clean_workspace() {
  civibuild restore --no-test
  [ -d "$EXMODULE" ] && rm -rf "$EXMODULE" || echo ""
}

function build_snapshot() {
  local name="$1"
  local snapdir="$SNAPSHOT_DIR/$EXMODULE-$SNAPSHOT_VER-$name/"
  local zipfile="$snapdir/original.zip"

  clean_workspace
  [ -f "$zipfile" ] && rm -f "$zipfile"
  [ ! -d "$snapdir" ] && mkdir -p "$snapdir"

  $CIVIX $VERBOSITY generate:module "$EXMODULE" --enable=no --no-interaction

  pushd "$EXMODULE"
  case "$name" in

    empty)
      echo "Nothing to add"
      ;;

    qf)
      $CIVIX $VERBOSITY generate:page MyPage civicrm/my-page
      $CIVIX $VERBOSITY generate:form MyForm civicrm/my-form
      ;;

    entity3)
      $CIVIX $VERBOSITY generate:upgrader
      $CIVIX $VERBOSITY generate:entity MyEntityThree -A3
#      $CIVIX $VERBOSITY generate:entity MyEntityThree
      $CIVIX $VERBOSITY generate:entity-boilerplate
      ;;

    entity34)
      $CIVIX $VERBOSITY generate:upgrader
      $CIVIX $VERBOSITY generate:entity MyEntityThreeFour -A3,4
      $CIVIX $VERBOSITY generate:entity-boilerplate
      ;;

    kitchensink)
      $CIVIX $VERBOSITY generate:api MyEntity Myaction
      $CIVIX $VERBOSITY generate:api MyEntity myaction2
      $CIVIX $VERBOSITY generate:case-type MyLabel MyName
      # $CIVIX $VERBOSITY generate:custom-xml -f --data="FIXME" --uf="FIXME"
      $CIVIX $VERBOSITY generate:entity MyEntityFour -A4
      $CIVIX $VERBOSITY generate:entity MyEntityThree -A3
#      $CIVIX $VERBOSITY generate:entity MyEntityThree
      $CIVIX $VERBOSITY generate:entity MyEntityThreeFour -A3,4
      $CIVIX $VERBOSITY generate:entity-boilerplate
      $CIVIX $VERBOSITY generate:form MyForm civicrm/my-form
      $CIVIX $VERBOSITY generate:form My_StuffyForm civicrm/my-stuffy-form
      $CIVIX $VERBOSITY generate:page MyPage civicrm/my-page
      $CIVIX $VERBOSITY generate:report MyReport CiviContribute
      $CIVIX $VERBOSITY generate:search MySearch
      $CIVIX $VERBOSITY generate:test --template=headless 'Civi\Civiexample\BarTest'
      $CIVIX $VERBOSITY generate:test --template=e2e 'Civi\Civiexample\EndTest'
      $CIVIX $VERBOSITY generate:test --template=phpunit 'Civi\CiviExample\PHPUnitTest'
      $CIVIX $VERBOSITY generate:upgrader
      $CIVIX $VERBOSITY generate:angular-module
      $CIVIX $VERBOSITY generate:angular-page FooCtrl foo
      $CIVIX $VERBOSITY generate:angular-directive foo-bar
      $CIVIX $VERBOSITY generate:theme
      $CIVIX $VERBOSITY generate:theme extratheme
      ;;

    *)
      echo "Error: unrecognized snapshot type $name" 1>&2
      exit 1
      ;;

  esac
  popd

  zip -r "$zipfile" "$EXMODULE"
}

################################################
## Didn't set a workspace? Educated guess...
if [ -z "$CIVIX_WORKSPACE" -a -d "$CIVIBUILD_HOME/dmaster/web/sites/all/modules/civicrm" ]; then
  export CIVIX_WORKSPACE="$CIVIBUILD_HOME/dmaster/web/sites/all/modules/civicrm/ext/civixtest"
  echo "Inferred CIVIX_WORKSPACE=$CIVIX_WORKSPACE"
fi

################################################
# Validate environment
if [ -z "$CIVIX_WORKSPACE" -o -z "$CIVIX_BUILD_TYPE" ]; then
  show_help 1>&2
  exit 1
fi

set -ex

################################################
# Main

if [ "$CIVIX_BUILD_TYPE" = "--phar" ]; then
  if [ ! -f "box.json" -o ! -f "build.sh" ]; then
    echo "Must call from civix root dir"
    exit 1
  fi
  ./build.sh
  CIVIX="$PWD"/bin/civix.phar
else
  composer install
  CIVIX="$PWD"/bin/civix
fi

[ ! -d "$CIVIX_WORKSPACE" ] && mkdir "$CIVIX_WORKSPACE"
pushd "$CIVIX_WORKSPACE"
  [ ! -d "$SNAPSHOT_DIR" ] && mkdir "$SNAPSHOT_DIR"
  build_snapshot empty
  build_snapshot qf
  build_snapshot entity3
  build_snapshot entity34
  build_snapshot kitchensink
  clean_workspace
popd

ls -l "$SNAPSHOT_DIR"
