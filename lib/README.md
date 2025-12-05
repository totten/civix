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

__Tip__: You may abbreviate the version-number (`Y.Y.beta1` => `Y.Y`) if...

1. The version `Y.Y` is core-frozen (_beta_), and if...
2. We haven't published a backport of `Y.Y` before, and if...
3. Testing of the mixin-backport is successful

Then you can safely say:

```bash
mv lib/civimix-schema@Y.Y.beta1.phar \
   lib/civimix-schema@Y.Y.phar
```

However, if there is a subsequent bugfix in `Y.Y`, then keep the third digit.

===> HRM, WAIT.  I'm not sure the loader will agree:

* Monday: Generate `foo@5.93.beta1.phar`. Rename to `foo@5.93.phar`
* Tuesday: Write an extension. It now includes `foo@5.93.phar`
* Wednesday: Update civicrm-core (5.93.beta1) with a bugfix for `foo`.
* Thursday: Run your extension again. You have access to two copies:
    * Core gives you `foo@5.93.beta1.1`.
    * Your extension has `foo@5.93`.
    * Based on `version_compare()`, your (older) extension copy takes precedence.
    * So it feels like the bug isn't actually fixed.
* Friday: Release new version of core. At this point:
    * Core gives you `foo@5.93.0.1`
    * Your extension still has `foo@5.93`.
    * Based on `version_compare()`, core's (newer) copy takes precedence.
    * Things become intuitive again.

For purposes of the loader (`version_compare`), the `5.93` is basically equivalent to `5.93.0`. (Both come after alpha/beta -- and before .1 and .2).

So this simplifying `Y.Y.beta`=>`Y.Y` feels OK because it will *normally* be OK, and it will *eventually* be OK.  But it will
misfire during RC. (And since we try to be diligent about bugs discovered during RC, it may send people on a goose chase...)
