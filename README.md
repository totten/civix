Civix is a command-line tool for building CiviCRM extensions.

### Requirements

* PHP 5.3+
* CiviCRM 4.1+ (4.2+ recommended) (http://civicrm.org)
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

### Upgrading

In early July 2012, several changes were made to civix's internal layout to
better align with Symfony Standard Edition.  Some steps which may help with
upgrading:

```bash
cd $HOME
php composer.phar self-update
cd civix
rm -rf vendor
php $HOME/composer.phar install
```

### CiviCRM Extension Basics

Before developing with civix, you should understand the basics of CiviCRM extensions:

* Before using any extensions, login to your development site and navigate to "**Manage Extensions**" screen ("Administer => Customize Data and Screens => Manage Extensions")
* The first time you view the screen, it will prompt you to configure an extensions directory. Do this.
* Remember the path to the extensions directory. In future commands, we will refer to it as $EXTDIR.
* To install or uninstall an extension, you will return to the "Manage Extensions" screen.
* There are four types of extensions:
  * **Modules**: These are useful for creating new features with a mix web-pages, web-forms, database tables, etc. Civix is mostly geared towards preparing modules.
  * **Reports**: A report plugs into CiviReports, which can export data and statistics using web-pages, PDFs, spreadsheets, emails, etc.
  * **Payment Processors**: (TODO)
  * **Custom Searches**: (TODO)

### Example: Initialize a new extension

Determine CiviCRM extension directory and
then navigate to it in bash:

```bash
# Determine the extension directory; go there
cd $EXTDIR

# Create new extension of type "module"
civix generate:module com.example.mymodule

# Alternatively, create new extension of type "report"
civix generate:report com.example.myreport CiviContribute

# Update the extension's metadata (author, license, etc)
cd com.example.mymodule
vi info.xml
```

To activate this new extension, browse to the "Manage Extensions" screen
where you can refresh the extension list and click "Install".

### Example: Add a basic web page

```bash
cd com.example.mymodule
civix generate:page Greeter civicrm/greeting
vi CRM/Mymodule/Page/Greeter.php
vi templates/CRM/Mymodule/Page/Greeter.tpl
```

Note: At time of writing, you must rebuild the menu to access this page:

  http://mysite.example.com/civicrm/menu/rebuild?reset=1

### Example: Package for distribution

Once you've implemented the extension, you can package the extension as a
.zip file for redistribution:

```bash
cd com.example.mymodule
civix build:zip
```

### Example: Add a database upgrade script

If your module needs to perform extra modifications to the database
during upgrades, you can add an "upgrader" class which is similar to
Drupal's hook_update_N.

```bash
cd com.example.mymodule
civix generate:upgrader
vi CRM/Mymodule/Upgrader.php
```
