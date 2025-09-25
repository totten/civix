$CIVIX $VERBOSITY generate:api MyEntity Myaction
$CIVIX $VERBOSITY generate:api MyEntity myaction2
$CIVIX $VERBOSITY generate:case-type MyLabel MyName
# $CIVIX $VERBOSITY generate:custom-xml -f --data="FIXME" --uf="FIXME"
$CIVIX $VERBOSITY generate:entity MyEntityFour
$CIVIX $VERBOSITY generate:form MyForm civicrm/my-form
$CIVIX $VERBOSITY generate:form My_StuffyForm civicrm/my-stuffy-form
$CIVIX $VERBOSITY generate:page MyPage civicrm/my-page
$CIVIX $VERBOSITY generate:report MyReport CiviContribute
$CIVIX $VERBOSITY generate:test --template=headless 'Civi\Civiexample\BarTest'
$CIVIX $VERBOSITY generate:test --template=e2e 'Civi\Civiexample\EndTest'
$CIVIX $VERBOSITY generate:test --template=phpunit 'Civi\CiviExample\PHPUnitTest'
$CIVIX $VERBOSITY generate:upgrader
$CIVIX $VERBOSITY generate:angular-module
$CIVIX $VERBOSITY generate:angular-page FooCtrl foo
$CIVIX $VERBOSITY generate:angular-directive foo-bar
$CIVIX $VERBOSITY generate:theme
$CIVIX $VERBOSITY generate:theme extratheme