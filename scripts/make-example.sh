#!/bin/bash

## NOTE: This script is deprecated. Consider update the PHPUnit suite instead. There's a lot of overlap with the snapshot testing.

## This script will:
## 1. Rebuild civix
## 2. Reset the database
## 3. Generate the $EXMODULE
## 4. Run tests from $EXMODULE

EXMODULE=${EXMODULE:-org.civicrm.civixexample}

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
# validate environment

set -ex
if [ ! -f "box.json" -o ! -f "build.sh" ]; then
  echo "Must call from civix root dir"
  exit 1
fi

# (re)build civix
[ -z "$SKIP_PHAR_BUILD" ] && ./build.sh
CIVIX=$PWD/bin/civix.phar
VERBOSITY=-v

if [ ! -d "$CIVIX_WORKSPACE" ]; then
  mkdir -p "$CIVIX_WORKSPACE"
fi

pushd "$CIVIX_WORKSPACE"
  # restore database
  civibuild restore

  # clean up any existing extension
  if [ -d "$EXMODULE" ]; then
    rm -rf "$EXMODULE"
  fi

  # generate module and try all the generators
  $CIVIX $VERBOSITY generate:module $EXMODULE --enable=no

  STATUS=$(cv ext:list -L /org.civicrm.civixexample/ --out=list --columns=status)
  if [ "$STATUS" = "installed" ]; then
    echo "Error: The example extension was installed prematurely"
    exit 1
  fi

  pushd $EXMODULE
    $CIVIX $VERBOSITY generate:api MyEntity Myaction
    $CIVIX $VERBOSITY generate:api MyEntity myaction2
    $CIVIX $VERBOSITY generate:case-type MyLabel MyName
    # $CIVIX $VERBOSITY generate:custom-xml -f --data="FIXME" --uf="FIXME"
    $CIVIX $VERBOSITY generate:entity MyEntityFour
    $CIVIX $VERBOSITY generate:entity MyEntityThree -A3
    $CIVIX $VERBOSITY generate:entity MyEntityThreeFour -A3,4
    $CIVIX $VERBOSITY generate:entity-boilerplate
    $CIVIX $VERBOSITY generate:form MyForm civicrm/my-form
    $CIVIX $VERBOSITY generate:form My_StuffyForm civicrm/my-stuffy-form
    $CIVIX $VERBOSITY generate:page MyPage civicrm/my-page
    $CIVIX $VERBOSITY generate:report MyReport CiviContribute
    $CIVIX $VERBOSITY generate:search MySearch
    $CIVIX $VERBOSITY generate:test CRM_Civiexample_FooTest
    $CIVIX $VERBOSITY generate:test --template=headless 'Civi\Civiexample\BarTest'
    $CIVIX $VERBOSITY generate:test --template=e2e 'Civi\Civiexample\EndTest'
    $CIVIX $VERBOSITY generate:test --template=phpunit 'Civi\CiviExample\PHPUnitTest'
    $CIVIX $VERBOSITY generate:upgrader
    $CIVIX $VERBOSITY generate:angular-module
    $CIVIX $VERBOSITY generate:angular-page FooCtrl foo
    $CIVIX $VERBOSITY generate:angular-directive foo-bar
    $CIVIX $VERBOSITY generate:theme
    $CIVIX $VERBOSITY generate:theme extratheme
  popd

  cv api extension.install key=$EXMODULE

  ## Make sure the unit tests are runnable.
  pushd $EXMODULE
    phpunit5 ./tests/phpunit/CRM/Civiexample/FooTest.php
    phpunit5 ./tests/phpunit/Civi/Civiexample/BarTest.php
    phpunit5 ./tests/phpunit/Civi/Civiexample/EndTest.php
    phpunit5 ./tests/phpunit/Civi/CiviExample/PHPUnitTest.php
    phpunit5 --group headless
    phpunit5 --group e2e
  popd

  ## Make sure all generated files pass linter.
  pushd "$EXMODULE"
    find tests -name '*.php' | xargs civilint
  popd
popd
