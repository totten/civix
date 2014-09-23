### v14.01.0

 * **generate:case-type** - Add command to generate CiviCase XML files
 * **generate:module** - Add support for generating license metadata by passing parameters "--license", "--author", and "--email".
   The information will be propagated to info.xml and LICENSE.txt
 * **generate:module** - Add hook stub for hook_civicrm_alterSettingsFolders (in module.php.php and module.civix.php.php)
 * **generate:module** - Add hook stub for hook_civicrm_caseTypes (in module.php.php and module.civix.php.php)
 * Add documentation links for hooks (using "@link")
 * Reformat civix source code based on CiviCRM's coding conventions

### v14.09.0

 * Add options **author**, **email**, **license** for setting defaults on new extensions
 * Remove options **civicrm_api3_conf_path**, **civicrm_api3_server**, **civicrm_api3_path**, **civicrm_api3_key**, and **civicrm_api3_api_key**
 * **civix** will scan the directory tree to locate and bootstrap the CMS. This requires that extensions be stored in a subdirectory somewhere under the CMS root.
 * **civix generate:module** - Automatically refresh extension list. Prompt for installation.
 * **civix generate:api** - Fix for v4.5

### v14.09.1

 * **civix generate:module** - Initialize URL tags in info.xml
 * **civix generate:entity** - Fix for v4.5
 * Fixes for misc PHPStorm warnings
