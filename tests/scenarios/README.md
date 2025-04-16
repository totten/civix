Define extra tests to supplement the `make-snapshots.sh` process.

When running `make-snapshots.sh`, you can give a list of scenarios like `qf`
and `entity4`.  Each corresponds to a subfolder.  For example, the `entity4`
scenario would have a structure like this

* `./tests/scenarios/entity4/`
    * `make.sh` (generate a new extension; mandatory)
    * `phpunit.xml.dist` (phpunit config; recommended)
    * `bootstrap.php` (phpunit bootstrap; recommended)
    * `MyTest.php` (phpunit test-case; recommended)
