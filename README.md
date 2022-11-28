Civix is a command-line tool for building CiviCRM extensions. It is distributed as part of [CiviCRM-Buildkit](https://github.com/civicrm/civicrm-buildkit).

### Requirements

* PHP 7.1.3+
* CiviCRM 5.0+ (installed from git http://github.com/civicrm)
* (For MAMP, WAMP, XAMPP, etc) PHP command-line configuration (http://wiki.civicrm.org/confluence/display/CRMDOC/Setup+Command-Line+PHP)
* (For CentOS/RHEL) Compatible version of libxml2 (https://github.com/totten/civix/issues/19)

### Download: Single Executable

Civix is distributed as a single, portable PHAR executable.  As long as you have PHP-CLI
properly configured, it should work as a simple download, e.g.

```bash
sudo curl -LsS https://download.civicrm.org/civix/civix.phar -o /usr/local/bin/civix
sudo chmod +x /usr/local/bin/civix
```

To upgrade an existing installation, simply re-download the latest `civix.phar`.

### Download: Git + Composer (Linux/OS X)

To download the source tree and all dependencies, use [`git`](https://git-scm.com) and [`composer`](https://getcomposer.org/), e.g.:

```
$ git clone https://github.com/totten/civix
$ cd civix
$ composer install
```

### Download: Git + Composer (Windows)

```
# Install composer
In a browser, visit http://getcomposer.org
Click on the download button.
Scroll down to Windows Installer and click on Composer-Setup.exe.
Choose Run when prompted.

# Install git
If you don't already have git, then in a browser visit http://git-scm.com/download/win.
Choose Run when prompted.
Leave all the defaults.

# Download civix
Decide where you want to install civix. You might want to put it in C:\Program Files, but you might get hassled about admin rights, in which case you can pick somewhere else, like C:\users\<your name>.
From the start menu choose All Programs -> Git -> Git Bash.
In the window that appears, type:
  cd "/c/users/<your name>"
  (note the forward slashes)
git clone git://github.com/totten/civix.git
exit

# Download dependencies
In windows explorer, navigate to C:\users\<your name> (or whereever you installed civix).
Shift-right-click on the civix folder.
Choose open command window here.
In the window that appears, type:
  composer install

# Add civix to the PATH
Either temporarily add it:
set PATH=%PATH%;C:\users\<your name>\civix\bin

OR permanently:
Start Menu -> Control Panel -> System -> Advanced -> Environment Variables
```

### Documentation

The [CiviCRM Developer Guide](https://docs.civicrm.org/dev/en/latest/) includes [tutorials for building extensions](https://docs.civicrm.org/dev/en/latest/extensions/civix/)

For reference documentation, civix supports a "--help" option.  For example,
to get reference materials about the "generate:page" command, run:

```bash
civix generate:page --help
```

### Development: Custom Build

If you are developing new changes to `civix` and want to create custom build of
`civix.phar` from source, you must have [`git`](https://git-scm.com), [`composer`](https://getcomposer.org/), and
[`box`](http://box-project.github.io/box2/) installed. Then run:

```
$ git clone https://github.com/totten/civix
...
$ cd civix
$ composer install
...
$ which box
/usr/local/bin/box
$ php -dphar.readonly=0 /usr/local/bin/box build
```

If you want to run with the same versions of PHP+box that are used for official builds, then run:

```
nix-shell --run ./build.sh
```

### Development: Testing

Automated testing for `civix` requires a live CiviCRM deployment -- where it can generate and install example extensions.  The deployment
must be amenable to CLI scripting (eg `civix`, `cv`, `civibuild`).  You should set the environment variable `CIVIX_WORKSPACE` to point to
a folder where we can safely fill+destroy extensions, such as:

```bash
export CIVIX_WORKSPACE=$HOME/bknix/build/dmaster/web/sites/all/modules/civicrm/ext/civixtest
```

Tests are divided into a few areas:

* PHPUnit: End-to-end tests (`tests/e2e/**Test.php`)
* PHPUnit: Unit tests (`src/CRM/CivixBundle/**Test.php`)
* Snapshots: Some of the PHPUnit-E2E tests build on a library of example code (`tests/snapshots/*`)
* `make-example.sh`: Script which runs all code-generators

PHPUnit is now preferred for testing (because it can support better assertions, better debugging, and better coding).

### Development: Testing: PHPUnit

Ensure that the `CIVIX_WORKSPACE` is set before running phpunit. Also, ensure that there is clean database to test against.
For example:

```bash
export CIVIX_WORKSPACE=$HOME/bknix/build/dmaster/web/sites/all/modules/civicrm/ext/civixtest
civibuild restore dmaster
phpunit8
```

The helper `./scripts/run-tests.sh` is a smaller which does the above.

### Development: Testing: Snapshots

There are several scripts for working with example extensions.

```bash
export CIVIX_WORKSPACE=$HOME/bknix/build/dmaster/web/sites/all/modules/civicrm/ext/civixtest

## Make new extensions with the current version of civix. Give a name to the version.
./scripts/make-snapshots.sh --phar --version v99.88

## Run the upgrade against the snapshots. Optionaly, filter by the name of the snapshots.
./scripts/upgrade-snapshots.sh
./scripts/upgrade-snapshots.sh /v22.*entity34/

## Remove any temporary data generated by the above
./scripts/tidy-snapshots.sh
```

### Development: Testing: Make Example

The deprecated bash script (`make-example.sh`) has been around longer and covers more functionality, but the assertions are limited,
and effective usage may require more effort. Example:

```bash
export CIVIX_WORKSPACE=$HOME/bknix/build/dmaster/web/sites/all/modules/civicrm/ext/civixtest

## Usage: scripts/make-example.sh
bash scripts/make-example.sh

## Make a copy of the original output.
cp -r ~/buildkit/build/dmaster/sites/all/modules/civicrm/tools/extensions/org.civicrm.civixexample{,-orig}

## Hack the code... then rerun...
bash scripts/make-example.sh ~/buildkit dmaster

## And see how the outputs changed.
colordiff -ru ~/buildkit/build/dmaster/sites/all/modules/civicrm/tools/extensions/org.civicrm.civixexample{-orig,}

## Tip: Use && to run the last two commands together
bash scripts/make-example.sh ~/buildkit dmaster && colordiff -ru ~/buildkit/build/dmaster/sites/all/modules/civicrm/tools/extensions/org.civicrm.civixexample{-orig,}
```
