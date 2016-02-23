Civix is a command-line tool for building CiviCRM extensions.

### Requirements

* PHP 5.3+
* CiviCRM 4.2+ (installed from git http://github.com/civicrm)
* (For MAMP, WAMP, XAMPP, etc) PHP command-line configuration (http://wiki.civicrm.org/confluence/display/CRMDOC/Setup+Command-Line+PHP)
* (For CentOS/RHEL) Compatible version of libxml2 (https://github.com/totten/civix/issues/19)

### Download

```bash
sudo curl -LsS https://download.civicrm.org/civix/civix.phar -o /usr/local/bin/civix
sudo chmod +x /usr/local/bin/civix
```

### Documentation

The CiviCRM wiki includes tutorials for building extensions. See:

http://wiki.civicrm.org/confluence/display/CRMDOC/Create+an+Extension

For reference documentation, civix supports a "--help" option.  For example,
to get reference materials about the "generate:page" command, run:

```bash
civix generate:page --help
```

### Build

Use `git`, [composer](https://getcomposer.org/), and [box](http://box-project.github.io/box2/):

```
$ git clone https://github.com/totten/civix
$ cd civix
$ composer install
$ php -dphar.readonly=0 `which box` build
```

### Test

There isn't a proper test-suite, but the script `tests/make-example.sh` will
run all the code-generators (with a given build/version of CiviCRM).  It's
not pretty, though -- it assumes you're using buildkit and Drupal
single-site.


```bash
## Usage: tests/make-example.sh <BUILDKIT_ROOT> <BUILDKIT_BUILD>
bash tests/make-example.sh ~/buildkit dmaster

## Make a copy of the original output.
cp -r ~/buildkit/build/dmaster/sites/all/modules/civicrm/tools/extensions/org.civicrm.civixexample{,-orig}

## Hack the code... then rerun...
bash tests/make-example.sh ~/buildkit dmaster

## And see how the outputs changed.
colordiff -ru ~/buildkit/build/dmaster/sites/all/modules/civicrm/tools/extensions/org.civicrm.civixexample{-orig,}

## Tip: Use && to run the last two commands together
bash tests/make-example.sh ~/buildkit dmaster && colordiff -ru ~/buildkit/build/dmaster/sites/all/modules/civicrm/tools/extensions/org.civicrm.civixexample{-orig,}
```
