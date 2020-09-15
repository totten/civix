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

### Development: Testing

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
