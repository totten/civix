Define extra tests to supplement the `make-snapshots.sh` process.

When running `make-snapshots.sh`, you can give a list of scenarios like `qf` and `entity4`.

You can add phpunit test-cases to these scenarios by creating matching entries in here.

For example, when creating a new extension in the style of `entity4`, it
will include any tests defined by `tests/scenarios/entity4/`.
