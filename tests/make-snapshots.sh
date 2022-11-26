#!/bin/bash

## Quick hack for manually testing all commands
BUILDDIR="$1"
BUILDNAME="$2"
WORKINGDIR="$BUILDDIR/build/$BUILDNAME/web/sites/all/modules/civicrm/tools/extensions"
EXMODULE=${EXMODULE:-org.example.civixsnapshot}
SNAPSHOT_DIR="$PWD/tests/snapshots"
SNAPSHOT_VER=$( git describe --tags )

################################################
function build_snapshot() {
  local name="$1"
  local zipfile="$SNAPSHOT_DIR/$EXMODULE-$SNAPSHOT_VER-$name.zip"

  civibuild restore "$BUILDNAME" --no-test
  [ -d "$EXMODULE" ] && rm -rf "$EXMODULE"
  [ -f "$zipfile" ] && rm -f "$zipfile"

  $CIVIX $VERBOSITY generate:module "$EXMODULE" --enable=no

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
# validate environment
if [ -z "$BUILDNAME" ]; then
  echo "Usage: $0 <buildkit-dir> <build-name>"
  echo "Running this will:"
  echo " 1. Rebuild civix"
  echo " 2. Reset the build's database"
  echo " 3. Generate a few variations of the $EXMODULE extension"
  echo " 4. Save each variation to $SNAPSHOT_DIR"
  exit 1
fi

if [ ! -d $WORKINGDIR ]; then
  echo "error: missing $WORKINGDIR"
  exit 1
fi

set -ex
#if [ ! -f "box.json" -o ! -f "build.sh" ]; then
#  echo "Must call from civix root dir"
#  exit 1
#fi
composer install

# (re)build civix
#[ -z "$SKIP_PHAR_BUILD" ] && ./build.sh
#CIVIX=$PWD/bin/civix.phar
CIVIX=$PWD/bin/civix
VERBOSITY=

pushd $WORKINGDIR
  [ ! -d "$SNAPSHOT_DIR" ] && mkdir "$SNAPSHOT_DIR"
  build_snapshot empty
  build_snapshot qf
  build_snapshot entity3
  build_snapshot entity34
  build_snapshot kitchensink
popd

ls -l "$SNAPSHOT_DIR"
