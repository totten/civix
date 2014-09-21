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
pushd "$BUILDDIR/build/$BUILDNAME/sites/all/modules/civicrm/tools/extensions"
  civibuild restore $BUILDNAME

  if [ -d "$EXMODULE" ]; then
    rm -rf "$EXMODULE"
  fi
  
  echo n | civix generate:module $EXMODULE
  pushd $EXMODULE
    civix generate:api MyEntity MyAction
    civix generate:case-type MyLabel MyName
    # civix generate:custom-xml -f --data="FIXME" --uf="FIXME"
    civix generate:entity MyEntity
    civix generate:form MyForm civicrm/my-form
    civix generate:page MyPage civicrm/my-page
    civix generate:report MyReport CiviContribute
    # civix generate:report-ext 
    civix generate:search MySearch
    civix generate:test CRM_Foo_MyTest
    civix generate:upgrader 
  popd

  drush cvapi extension.install key=$EXMODULE
popd
