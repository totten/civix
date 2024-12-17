# civix: Mixin Libraries

## Generate an update

Consider `civimix-schema@X.X.X.phar`, which is generated from `civicrm-core:mixin/lib/civimix-schema`.

Suppose a new version (`Y.Y.Y`) is available upstream.

Here's how to update the version civix:

```bash
## Export new lib
cd CIVICRM_REPO
./tools/mixin/bin/build-lib /tmp/civimix

## Update civix: Remove old lib. Add new lib.
cd CIVIX_REPO
rm lib/civimix-schema@X.X.X.phar
cp /tmp/civimix/civimix-schema@Y.Y.Y.phar lib/
```
