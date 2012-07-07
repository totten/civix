Civix is a command-line tool for building CiviCRM extensions.

== Installation ==

1. If you haven't already, install the PHP tool "composer" (getcomposer.org):

$ cd $HOME
$ curl -s http://getcomposer.org/installer | php

2. Get civix source code and any dependencies:

$ php composer.phar create-project civicrm/civix

3. Copy and edit the example configuration file

cp app/config/parameters.yml.dist app/config/parameters.yml
vi app/config/parameters.yml

4. Add the new civix folder to your shell's PATH, e.g.

$ export PATH=$HOME/civix:$PATH

== Upgrading ==

In early July 2012, several changes were made to civix's internal layout to
better align with Symfony Standard Edition.  Some steps which may help with
upgrading:

$ php composer.phar self-update
$ cd civix
$ rm -rf vendor
$ php composer.phar install

== Example: Initialize a new extension ==

Configure a CiviCRM extension directory (e.g. /home/myuser/extensions) and
then navigate to it in bash:

$ cd /home/myuser/extensions

For a new module-style extension or report-style extension, use:

$ civix generate:module com.example.mymodule
  (or)
$ civix generate:report com.example.myreport CiviContribute

You should tweak the "com.example.mymodule/info.xml" file.

To activate this new module, browse to

  http://mysite.example.com/civicrm/admin/extensions?reset=1&action=browse

where you can refresh the extension list and enable the extension.

== Example: Add a basic web page ==

$ cd com.example.mymodule
$ civix generate:page Greeter civicrm/greeting
$ vi CRM/Mymodule/Page/Greeter.php
$ vi templates/CRM/Mymodule/Page/Greeter.tpl

Note: At time of writing, you must rebuild the menu to access this page:

  http://mysite.example.com/civicrm/menu/rebuild?reset=1

== Example: Build a .zip file ==

Once you've implemented the extension, you can build a .zip file for
redistribution:

$ cd com.example.mymodule
$ civix build:zip

== Example: Add a database upgrade script ==

If your module needs to perform extra modifications to the database
during upgrades, you can add an "upgrader" class which is similar to
Drupal's hook_update_N.

$ cd com.example.mymodule
$ civix generate:upgrader
$ vi CRM/Mymodule/Upgrader.php
