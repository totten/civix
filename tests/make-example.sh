#!/bin/bash

## Quick hack for manually testing all commands
BUILDDIR="$1"
BUILDNAME="$2"
EXMODULE=org.civicrm.civixexample
if [ -z "$BUILDNAME" ]; then
  echo "Usage: $0 <buildkit-dir> <build-name>"
  echo "Note: Running this will reset the build's database"
  exit 1
fi

if [ ! -d "$BUILDDIR/build/$BUILDNAME/sites/all/modules/civicrm/tools/extensions" ]; then
  echo "error: missing $BUILDDIR/build/$BUILDNAME/sites/all/modules/civicrm/tools/extensions"
  exit 1
fi

set -ex
if [ ! -f "box.json" ]; then
  echo "Must call from civix root dir"
  exit 1
fi
php -dphar.readonly=0 `which box` build
CIVIX=$PWD/bin/civix.phar

pushd "$BUILDDIR/build/$BUILDNAME/sites/all/modules/civicrm/tools/extensions"
  civibuild restore $BUILDNAME

  if [ -d "$EXMODULE" ]; then
    rm -rf "$EXMODULE"
  fi

  echo n | $CIVIX -v generate:module $EXMODULE
  pushd $EXMODULE
    $CIVIX -v generate:api MyEntity MyAction
    $CIVIX -v generate:case-type MyLabel MyName
    # $CIVIX -v generate:custom-xml -f --data="FIXME" --uf="FIXME"
    $CIVIX -v generate:entity MyEntity
    $CIVIX -v generate:form MyForm civicrm/my-form
    $CIVIX -v generate:page MyPage civicrm/my-page
    $CIVIX -v generate:report MyReport CiviContribute
    $CIVIX -v generate:search MySearch
    $CIVIX -v generate:test CRM_Civiexample_FooTest
    $CIVIX -v generate:test --type=headless 'Civi\Civiexample\BarTest'
    $CIVIX -v generate:test --type=e2e 'Civi\Civiexample\EndTest'
    $CIVIX -v generate:upgrader
    $CIVIX -v generate:angular-module
    $CIVIX -v generate:angular-page FooCtrl foo
    $CIVIX -v generate:angular-directive foo-bar
  popd

  cv api extension.install key=$EXMODULE

  ## Make sure the unit tests are runnable.
  pushd $EXMODULE
    phpunit4 ./tests/phpunit/CRM/Civiexample/FooTest.php
    phpunit4 ./tests/phpunit/Civi/Civiexample/BarTest.php
    phpunit4 ./tests/phpunit/Civi/Civiexample/EndTest.php
    phpunit4 --group headless
    phpunit4 --group e2e
   popd
popd
