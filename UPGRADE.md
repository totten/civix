### Upgrade: General

From time-to-time, the templates in civix may change. If you want to update
your module to follow the newer templates, then it is a good idea to follow
this procedure:

1. Make sure you have a backup of your code. If you use version-control (git/svn), then you should be good to go.
2. In the shell, navigate to the extension base directory. (If the extension is "org.example.myext" and it lives in
   "/var/www/extensions/org.example.myext", then navigate to "/var/www/extensions".)
3. Re-run the "civix generate:module" ocmmand (e.g. "civix generate:module org.example.myext"). This will regenerate
   the *.civix.php file (e.g. "/var/www/extensions/org.example.myext/myext.civix.php").
4. Compare the new code with the old code. (e.g. "git diff" or "svn diff")
5. Look for additional, version-specific upgrade steps (below)

### Upgrade: v13.10 to v14.01

Beginning with v14.01, civix includes implementations of these hooks:

 * hook_civicrm_caseTypes: Civix will scan for any CiviCase XML files in 
   "xml/case/*.xml" and automatically register these.
 * hook_alterSettingsFolders: Civix will scan for any settings files in
   "settings/*.setting.php" and automatically register these.

For existing extensions, you should update the main PHP file -- eg if the
main PHP file for the extension is
"/var/www/extensions/org.example.myext/myext.php", then add:

```php
/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 */
function myext_civicrm_caseTypes(&$caseTypes) {
  _myext_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsMetaData
 */
function myext_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _myext_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
```
