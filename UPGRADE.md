# Civix Upgrade Guide

Extensions produced by `civix` include a mix of custom code and boilerplate code.  From time-to-time, you may wish to
update the boilerplate code (eg to enable new functionality or to fix bugs).

In `civix` v22.05+, there is a built-in upgrade assistant:

```bash
cd myextension
civix upgrade
```

This command may perform common tasks like:

* Add or remove tags in `info.xml`
* Add or remove stub functions in the main PHP file (like `myextension.php`)
* Regenerate reserved files (like `myextension.civix.php`)

This process is semi-automatic. Some well-defined tasks run automatically;
others require extra communication or decision-making.

## Typical workflow (abstract)

1. Make sure you have a backup of your code. If you use version-control (`git`/`svn`), then you should be good to go.
2. In the shell, navigate to the target extension directory. (If the extension is `org.example.myext`, then the path may look like `/var/www/extensions/org.example.myext`.)
3. Run the `civix upgrade` command. This will inspect the codebase, regenerate boilerplate (eg `*.civix.php`), provide a log of changes,
   and (in some cases) provide extra questions or extra information about the upgrade.
4. Compare the new code with the old code (e.g. `git diff` or `svn diff`).
5. Review any new/relevant items [Special Tasks](#special-tasks).
6. Perform any QA (as you normally would for changes in the extension).

## Typical workflow (git)

```bash
cd myextension
git checkout -b my-civix-upgrade
civix upgrade
git status
git diff
git add .
git commit -m 'Upgraded civix templates'
```

## Special Tasks

Some changes are not automated.  These changes are (generally) optional and subjective -- e.g.  they may introduce new options, methods, or
template-code that improves the system.  If you ignore them, the extension will continue working as before (unless noted otherwise).

Special-tasks are organized based on when the functionality was introduced.  (Ex: `v21.09.*` would indicate functionality that was added or modified
circa September 2021.)

### Upgrade to v21.09.0+: Angular Module

Angular code in Civi extensions usually has one of these layouts:

* (A) (*default, best supported*) There is **one** Angular module, and its name **exactly matches** the Civi extension.
* (B) There is **one** Angular module, and its name does *not* match the Civi extension.
* (C) There are **multiple** Angular modules. It is **impossible** for them to all match.

This version improves support for (B) (*one module, mismatched name*). You may now provide a hint via `info.xml`. For example, if the extension is `foobar` and the Angular module is `crmFoobar`, then set:

```xml
<extension key="com.example.foobar" type="module">
  <file>foobar</file>
  <civix>
    <angularModule>crmFoobar</angularModule>
  </civix>
</extension>
```

For (B), this will improve usability - when calling `generate:angular-*` commands, it will use a better the default value of `--am=...`.

There is no impact for (A) and (C).

### Upgrade to v20.09.0+: APIv3 Entity

Some versions of `generate:entity` (late 2019/early 2020) created incorrect boilerplate for APIv3.  This affected the
file `api/v3/{MyEntity}.php` and the function `civicrm_api3_{my_entity}_get()`.  The function may look like one of these 3 revisions:

```php
// Revision 1 - The results will conform with APIv3 standards, but this may not be robust if
// there are other problems with entity metadata.
return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);

// Revision 2 - This is more robust against metadata problems, but the result-format does not conform
// with APIv3 standards. It omits the header/wrapper ("is_error", "values", etc) and has an unquoted string.
return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE, MyEntity);

// Revision 3 - This is conformant and robust.
return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'MyEntity');
```

If you currently have revision 2, then you should certainly fix the missing quotes.  However, there is a choice about
whether to fix the boolean:

* __Switch to `TRUE`__: The output will have standard APIv3 formatting, but any existing callers may break.
* __Leave as `FALSE`__: The output will have non-conventional formatting, but existing callers will work.

### Upgrade to v19.11.0+: APIv4 and PSR-4

APIv4 looks for classes in the `Civi\Api4` namespace and `Civi/Api4` folder. 
To support generation of APIv4 code, the `info.xml` should have a
corresponding classloader:

```xml
  <classloader>
    <psr4 prefix="Civi\" path="Civi" />
  </classloader>
```

### Upgrade to v18.02.0+: PHPUnit (Optional)

The template for `tests/phpunit/bootstrap.php` changed slightly to make `phpunit` work in symlinked directory structures. You may want to manually apply the changes from https://github.com/totten/civix/pull/121.

### Upgrade to v17.10.0+: Test Files

The PHPUnit bootstrap file (`tests/phpunit/bootstrap.php`) has been updated to support autoloading of utility classes within your extensions `tests` folder. To follow this revised convention, update `bootstrap.php`. After the the call to `eval(...);`, say:

```php
$loader = new \Composer\Autoload\ClassLoader();
$loader->add('CRM_', __DIR__);
$loader->add('Civi\\', __DIR__);
$loader->register();
```

### Upgrade to v17.08.1+: The Big `E`

civix v17.08.1 makes corrections to the behavior of the new helpers, `E::path()` and `E::url()`. They are now
more consistent in that:

 * `E::path()` and `E::url()` (without arguments) both return the folder *without* a trailing `/`.
 * `E::path($file)` and `E::url($file)` (with an argument) both return the folder plus `/` plus filename.

Suggestion: search your codebase for instances of `E::path` or `E::url` to ensure consistent path construction.

### Upgrade to v17.08.0+: The Big `E`

civix v17.08.0+ introduces a new helper class. You can generate it by following the "General Tasks" (above). No other changes are required.

Optionally, if you want to *use* this helper class, then add a line like this to your other `*.php` files:

```php
use CRM_Myextension_ExtensionUtil as E;
```

### Upgrade to v16.10+: Upgrader postInstall (optional)

*(See also: "General Tasks: Upgrader Class")*

In version 16.10.0, hook_civicrm_postInstall was implemented in the extension's
main PHP file and delegated to the Upgrader base class. If you wish to run
your own code post-install, you should copy the following snippet (or something
like it) into the Upgrader class (e.g. "/var/www/extensions/org.example.myext/CRM/Myext/Upgrader.php"):

```php
/**
  * Example: Work with entities usually not available during the install step.
  *
  * This method can be used for any post-install tasks. For example, if a step
  * of your installation depends on accessing an entity that is itself
  * created during the installation (e.g., a setting or a managed entity), do
  * so here to avoid order of operation problems.
  */
 public function postInstall() {
   $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
     'return' => array("id"),
     'name' => "customFieldCreatedViaManagedHook",
   ));
   civicrm_api3('Setting', 'create', array(
     'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
   ));
 }
```

### Upgrade v16.03.2+: Test Files

Prior to civix v16.03, civix included the commands `civix generate:test` and `civix test`.  Beginning with v16.03, civix templates now
comply with the [Testapalooza PHPUnit Template](https://github.com/civicrm/org.civicrm.testapalooza/tree/phpunit).  The key changes:

 * Tests are now executed directly with standalone `phpunit`. There is no need for `civix test` or for Civi's embedded `phpunit`.
   This should enable better support for IDEs and other tools.
 * The code-generator creates two additional files, `phpunit.xml.dist` and `tests/phpunit/bootstrap.php`.
   These are requirements for using standalone `phpunit`.
 * Tests use `PHPUnit_Framework_TestCase` with [`Civi\Test`](https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md) instead of `CiviUnitTestCase`. This gives you more control over the
   test environment.
 * You must have [`cv`](https://github.com/civicrm/cv) installed when running tests.

Given that there isn't a very large body of existing extension tests, we haven't thoroughly tested the migration path, but
here are some expectations:

 * The command `civix test` hasn't changed.  If you used it before to run your existing tests, then you should still be able to
   use it now. However, it's deprecated.
 * The civicrm-core repo still has `tools/scripts/phpunit`. If you used it before run your existing tests, then you should still
   be able to use it now.
 * If you want to start using standalone `phpunit`, then:
   * You need to create `phpunit.xml.dist` and `tests/phpunit/bootstrap.php`. These files will be autogenerated the next time
     you use `civix generate:test`.
   * You should update the existing tests to implement the `HeadlessInterface`, to define function `setupHeadless()`, and to
     declare `@group headless`. This will ensure that the headless system boots correctly when running your test.
   * Try creating a new test using the `legacy` template, e.g. `civix generate:test --template=legacy CRM_Myextension_DummyTest`.
     This will generate `phpunit.xml.dist` and `tests/phpunit/bootstrap.php`, *and* it will create an example of using `CiviUnitTestCase`.
   * Note: Legacy tests executed this way may reset key variables (e.g. `CRM_Core_Config::singleton()`) extra times.
     However, the pool of existing extension tests is fairly small, so we don't expect this to have a big real-world impact.

### Upgrade to v15.10+: hook_civicrm_navigationMenu

Prior to v4.7, the hook for manipulating the navigation menu required that the
extension author compute a `navID` and `parentID` for each new menu entry, but the
common examples for doing this were error-prone. In v4.7, the `navID` and `parentID`
may be omitted and computed automatically.

For backward compatibility, `civix` provides an adapter -- simply declare the menu
item (without `navID` or `parentID`; as you would in v4.7) and then delegate to
the helper function for `navigationMenu`.

```php
/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function myext_civicrm_navigationMenu(&$menu) {
  _myext_civix_insert_navigation_menu($menu, NULL, array(
    'label' => ts('The Page', array('domain' => 'org.example.myext')),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _myext_civix_navigationMenu($menu);
}
```
