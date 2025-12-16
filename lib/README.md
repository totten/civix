# civix: Mixin Libraries

## Generate an update

Consider `civimix-schema@X.X.X`, which is:

* Developed canonically in `civicrm-core.git`:`mixin/lib/civimix-schema/`
* Generated as PHAR with `civicrm-core.git`:`tools/mixin/bin/build-lib`
* Distributed as a civix backport in `civix.git`:`lib/`

Here's are some steps for backporting a newer version of `civimix-schema@X.X.X.phar`:

```bash
## Export new version
cd CIVICRM_REPO
./tools/mixin/bin/build-lib /tmp/civimix

## Update civix: Remove old version
cd CIVIX_REPO
git rm lib/civimix-schema@*.phar

## Update civix: Add new version
cp /tmp/civimix/civimix-schema@*.phar lib/
git add lib/civimix-schema@*.phar

## And commit...
git commit -m 'Update civimix-schema (X.X.X => Y.Y.Y)'
```
