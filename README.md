Civix is a command-line tool for building CiviCRM extensions.

### Requirements

* PHP 5.3+
* CiviCRM 4.1+ (4.2+ strongly recommended) (http://civicrm.org)
* Composer (http://getcomposer.org)
* git

### Installation

```bash
cd $HOME

# If you haven't already, install the PHP tool "composer"
curl -s http://getcomposer.org/installer | php

# Download civix and dependencies
git clone https://github.com/totten/civix.git
cd civix
cp app/config/parameters.yml.dist app/config/parameters.yml
php $HOME/composer.phar install

# Add civix to the PATH
export PATH=$HOME/civix:$PATH
```

### Upgrade

To upgrade civix and its dependencies, one can normally do:

```bash
cd $HOME/civix
git pull
php $HOME/composer.phar install
```

On some occasions, changes in civix, in a dependency, or in composer can
break the upgrade.  If this happens, then try deleting the dependencies and
re-installing them:

```bash
php $HOME/composer.phar self-update
cd $HOME/civix
git pull
rm -rf vendor
php $HOME/composer.phar install
```

### Documentation

The CiviCRM wiki includes tutorials for building extensions. See:

http://wiki.civicrm.org/confluence/display/CRMDOC42/Create+an+Extension

For reference documentation, civix supports a "--help" option.  For example,
to get reference materials about the "generate:page" command, run:

```bash
civix generate:page --help
```
