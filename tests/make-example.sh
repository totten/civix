#!/bin/bash

## Quick hack for manually testing all commands
BUILDDIR="$1"
BUILDNAME="$2"
WORKINGDIR="$BUILDDIR/build/$BUILDNAME/web/sites/all/modules/civicrm/tools/extensions"
EXMODULE=org.civicrm.civixexample

# validate environment
if [ -z "$BUILDNAME" ]; then
  echo "Usage: $0 <buildkit-dir> <build-name>"
  echo "Running this will:"
  echo " 1. Rebuild civix"
  echo " 2. Reset the build's database"
  echo " 3. Over-write the $EXMODULE extension"
  exit 1
fi

if [ ! -d $WORKINGDIR ]; then
  echo "error: missing $WORKINGDIR"
  exit 1
fi

set -ex
if [ ! -f "box.json" ]; then
  echo "Must call from civix root dir"
  exit 1
fi

# (re)build civix
php -dphar.readonly=0 `which box` build
CIVIX=$PWD/bin/civix.phar
VERBOSITY=-v

pushd $WORKINGDIR
  # restore database
  civibuild restore $BUILDNAME

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
    $CIVIX $VERBOSITY generate:api MyEntity MyAction
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
    $CIVIX $VERBOSITY generate:test --template=legacy CRM_Civiexample_LegacyTest
    $CIVIX $VERBOSITY generate:test --template=headless 'Civi\Civiexample\BarTest'
    $CIVIX $VERBOSITY generate:test --template=e2e 'Civi\Civiexample\EndTest'
    $CIVIX $VERBOSITY generate:test --template=phpunit 'Civi\CiviExample\PHPUnitTest'
    $CIVIX $VERBOSITY generate:codeception-config
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
    phpunit5 ./tests/phpunit/CRM/Civiexample/LegacyTest.php
    phpunit5 ./tests/phpunit/Civi/Civiexample/BarTest.php
    phpunit5 ./tests/phpunit/Civi/Civiexample/EndTest.php
    phpunit5 ./tests/phpunit/Civi/CiviExample/PHPUnitTest.php
    phpunit5 --group headless
    phpunit5 --group e2e

    codecept generate:cest acceptance HelloWorld
    codecept run
  popd

  ## Make sure all generated files pass linter.
  ## ... Except codeception. Because we don't own that code-generator.
  pushd "$EXMODULE"
    find tests -name '*.php' \
      | grep -v tests/.*Cest.php \
      | xargs civilint
  popd
popd
