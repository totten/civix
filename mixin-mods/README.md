# mixin-mods

This folder contains civix-specific forks of some mixins. Guidelines:

* Always use a lower number than the current published.
* Keep the semantics/behavior in close alignment with same-version upstream.
* If you can't provide the same contract, then don't bother trying to backport.
* Only make changes for backward compatibility (Civi APIs, PHP APIs).
* To make a new variant, make a new copy. Keep old variants around for reference.
* You still need to maintain `mixin-backports.php`.
