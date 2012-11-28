Civix is a command-line tool for building CiviCRM extensions.

### Requirements

* PHP 5.3+
* CiviCRM 4.2+ (installed from SVN with r42790+ -- http://svn.civicrm.org/civicrm)
* Composer (http://getcomposer.org)
* git

### Installation

```bash
cd $HOME

# If you haven't already, install the PHP tool "composer"
curl -s http://getcomposer.org/installer | php

# Download civix and dependencies
git clone git://github.com/totten/civix.git
cd civix
php $HOME/composer.phar install

# Add civix to the PATH
export PATH=$HOME/civix:$PATH

# Link civix with a developmental CiviCRM site/source-tree.
# This requires knowing the directory which contains civicrm.settings.php
# For example, using a default installation of Drupal-CiviCRM with Debian/Ubuntu,
# the path might be "/var/www/drupal/sites/default/".
civix config:set civicrm_api3_conf_path /var/www/drupal/sites/default/

# To validate that the link is working, run:
civix civicrm:ping
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
