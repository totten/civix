Civix is a code-generator. It produces projects with some common conventions. Over
time, the conventions may change. The `upgrades/` folder helps manage the changes.

## Mental Model

Imagine we were a bit more bureacratic and we enjoyed writing documentation more. We might define coding conventions explicitly:

* "Civix Specification v19.06.0" is the convention used by civix beginning around June 2019 (revision 0).
* "Civix Specification v20.06.0" is the convention used by civix beginning around June 2020 (revision 0).
* "Civix Specification v22.05.0" is the convention used by civix beginning around May 2022 (revision 0).

As we bless each new specification, we find that all the pre-existing extensions are out-of-date. To continue
using `civix`, these extensions need to be brought up-to-date.

Of course, we are too lazy ("too efficient") to write a real specification. (The specifications wouldn't change much
day-to-day, and most people wouldn't bother reading them!) Instead, we pay attention for changes that matter, and
(*when needed*) we write an automatic upgrade script (`upgrades/19.06.0.up.php`, `upgrades/20.06.0.up.php`, etc).

## Algorithm

Every extension managed by `civix` should have a field that identifies which convention it uses, e.g.

```xml
<extension key="myextension">
  <civix>
    <format>20.06.0</format>
  </civix>
</extension>
```

An extension developer should periodically run the command `civix upgrade`. This compares the recorded `<format>` against the list
in `civix:upgrades/YY.MM.N.up.php`. It executes all subsequent upgrades in order, and it records the new `<format>`.

## Guidelines

* When a new upgrade-script is merged into `civix.git`, then it canonizes a new specification.
* The upgrade scripts are named `upgrades/YY.MM.N.up.php`.
    * Versions must be monotonic increasing.
    * Choose `YY.MM` based on _when you expect the change to be merged into `civix.git`_.
    * You may safely skip numbers, but retroactively injecting old numbers is confusing.
* If a `civix.git` PR adds an upgrade-script but takes a long time to get merged... then you _may_ need to re-number the upgrade-step.
* The version of "Civix Specification" is not exactly the same as the version of the `civix.phar` release.
    * Many updates to `civix.phar` do not require any changes to "Civix Specification".
    * One update to `civix.phar` may include multiple updates to "Civix Specification".
    * Changes to the "Civix Specification" (`upgrades/*.php`) __do necessarily__ require updates to any/all downstream extensions. (At a minimum, the `<format>` will be bumped.)
    * Changes to `civix.phar` __do not necessarily__ require updates to any downstream extensions.
