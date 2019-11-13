## General Tasks

From time-to-time, the templates in civix may change. If you want to update
your module to match the newer templates, then use this procedure:

1. Make sure you have a backup of your code. If you use version-control (git/svn), then you should be good to go.
2. In the shell, navigate to the extension base directory. (If the extension is "org.example.myext" and it lives in
   "/var/www/extensions/org.example.myext", then navigate to "**/var/www/extensions**".)
3. Re-run the "civix generate:module" command (e.g. "**civix generate:module org.example.myext**"). This will regenerate
   the *.civix.php file (e.g. "/var/www/extensions/org.example.myext/myext.civix.php").
4. Compare the new code with the old code (e.g. "**git diff**" or "**svn diff**").
5. Look for additional, version-specific upgrade steps (below).

### General Tasks: Hook Stubs

Sometimes new versions introduce new hook stubs. These generally are not
mandatory.  However, in civix documentation and online support, we will
assume that they have been properly configured, so it's recommended that you
update your extension's main PHP file.  For example, if the main PHP file
for the extension is "/var/www/extensions/org.example.myext/myext.php", the
snippets mentioned below (adjusting `myext` to match your extension).

Hook stubs are documented below as special tasks.

### General Tasks: Upgrader Class

Sometimes new versions introduce changes to the `Upgrader` classes (e.g.,
`CRM_Myext_Upgrader_Base` and its child). These generally are not mandatory --
CiviCRM is largely agnostic as to how modules manage schema upgrades -- but
`civix` suggests a reasonable approach to doing so, and documentation and online
support will assume this approach.

The steps for upgrading the `Upgrader` are as follows:

1. Make sure you have a backup of your code. If you use version-control (git/svn), then you should be good to go.
2. In the shell, navigate to your extension's root directory (e.g., "/var/www/extensions/org.example.myext").
3. Re-run the **civix generate:upgrader** command. This will regenerate the upgrader base class
   (e.g. "/var/www/extensions/org.example.myext/CRM/Myext/Upgrader/Base.php").
4. Compare the new code with the old code (e.g. "**git diff**" or "**svn diff**").
5. Look for additional, version-specific upgrade steps (below).

## Special Tasks

### Upgrade to v19.11.0+: APIv4 and PSR-4

APIv4 looks for classes in the `Civi\Api4` namespace and `Civi/Api4` folder. 
To support generation of APIv4 code, the `info.xml` should have a
corresponding classloader:

```xml
  <classloader>
    <psr4 prefix="Civi\" path="Civi" />
  </classloader>
```

### Upgrade to v19.06.2+: PHPUnit (Optional; #155)

The templates for PHPUnit tests have been updated to match a major
transition in PHPUnit -- *all upstream base-classes were renamed*:

* `PHPUnit_Framework_TestCase` is the base-class in PHPUnit 4 and earlier
* `\PHPUnit\Framework\TestCase`` is the base-class in PHPUnit 6 and later
* PHPUnit 5 is a transitional version which supports both naming conventions.

In recent years, documentation+tooling in Civi have encouraged usage of
PHPUnit 5, so (hopefully) most environments are compatible with the newer naming.

Going forward, `civix` will generate templates using the newer naming.

To be consistent and forward-compatible, you should consider updating your
existing unit-tests to use the name base-classes.

### Upgrade to v19.06.2+: hook_civicrm_themes

Civix-based modules should implement `hook_civicrm_themes` to handle any
theme registrations.

At time of writing, the functionality is flagged as *experimental*.
Never-the-less, you may safely add the associated hook stub (regardless of
whether you use the functionality).

```php
/**
 * Implements hook_civicrm_themes().
 */
function myext_civicrm_themes(&$themes) {
  _myext_civix_civicrm_themes($themes);
}
```

### Upgrade to v18.02.0+: hook_civicrm_entityTypes

Civix-based modules should pass metadata about custom database entities
through `hook_civicrm_entityTypes`.

At time of writing, the functionality is flagged as *experimental*.
Never-the-less, you may safely add the associated hook stub (regardless of
whether you use the functionality).

```php
/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function myext_civicrm_entityTypes(&$entityTypes) {
  _myext_civix_civicrm_entityTypes($entityTypes);
}
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

### Upgrade to v16.10+

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

### Upgrade to v16.10+: hook_civicrm_postInstall

Prior to v16.10.0, extension schema versions were stored in the `civicrm_settings`
table under the namespace `org.example.myext:version`. This storage
mechanism proved problematic for multisites utilizing more than one domain (see
[CRM-19252](https://issues.civicrm.org/jira/browse/CRM-19252)). `civix` now
utilizes `hook_civicrm_postInstall` and an [updated Upgrader](#upgrade-to-v1609) to
store schema versions in the `civicrm_extension` table.

```php
/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function myext_civicrm_postInstall() {
  _myext_civix_civicrm_postInstall();
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

### Upgrade to v15.04+: hook_civicrm_angularModules

Civix-based modules should scan for Angular modules names in `ang/*.ang.php`
and auto-register them with the Civi-Angular base app (`civicrm/a/#`).

```php
/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function myext_civicrm_angularModules(&$angularModules) {
  _myext_civix_civicrm_angularModules($angularModules);
}
```

### Upgrade to v14.01+: hook_civicrm_caseTypes

Civix-based modules should scan for any CiviCase XML files in
`xml/case/*.xml` and automatically register these.

```php
/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function myext_civicrm_caseTypes(&$caseTypes) {
  _myext_civix_civicrm_caseTypes($caseTypes);
}
```

### Upgrade to v14.01+: hook_civicrm_alterSettingsFolders

Civix-based modules should scan for any settings files in
`settings/*.setting.php` and automatically register these.

```php
/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function myext_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _myext_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
```
