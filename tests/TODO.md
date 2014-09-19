Civix currently does not have an automated test-suite. The aim of this file is to sketch out the logic for such a
test-suite.

General
=======

 * Civix aims to support multiple versions of CiviCRM (v4.2+). It should be possible to run the test-suite on top of
   any version of CiviCRM.
 * Main concern is end-to-end testing
 * For each test-run, we should start from pristine DB (a la "civibuild restore")

civix generate:module
=====================

```
assert: extension is not known/active
execute: civix generate:module org.example.foo
execute: drush cvapi extension.install key=org.example.foo
assert: extension is active
```

civix generate:upgrader
=======================

```
assert: extension is not known/active
execute: civix generate:module org.example.foo
execute: drush cvapi extension.install key=org.example.foo
assert: db does not contain table "civicrm_example_foo"
execute: civix generate:upgrader
modify Upgrader.php to define table "civicrm_example_foo"
execute: drush cvapi extension.upgrade
assert: db does contain table "civicrm_example_foo"
```

civix generate:api
==================

```
assert: extension is not known/active
execute: civix generate:module org.example.foo
execute: civix generate:api Foo Bar
execute: drush cvapi foo.bar
assert: failure
execute: drush cvapi extension.install key=org.example.foo
execute: drush cvapi foo.bar
assert: success
```

civix generate:custom-xml
=========================

```
assert: extension is not known/active
execute: drush cvapi customgroup.create
execute: druah cvapi customfield.create
assert: customgroup & field exist
execute: civix generate:module org.example.foo
execute: civix generate:custom-xml
execute: restore snapshot
assert: customgroup & field do not exist
execute: drush cvapi extension.install key=org.example.foo
assert: customgroup & field exist
```

TODO
====

 * civix generate:case-type
 * civix generate:entity
 * civix generate:form
 * civix generate:module
 * civix generate:page
 * civix generate:report
 * civix generate:report-ext
 * civix generate:search
 * civix generate:test
