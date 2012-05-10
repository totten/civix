Civix is a command-line tool for building CiviCRM extensions.

== Installation ==

1. If you haven't already, install the PHP tool "composer" (getcomposer.org):

$ cd $HOME
$ curl -s http://getcomposer.org/installer | php

2. Get civix source code and any dependencies:

$ php composer.phar create-project civicrm/civix

4. Add the new civix folder to your shell's PATH, e.g.

$ export PATH=$HOME/civix:$PATH

== Example: Initialize a new extension ==

$ cd <civicrm-extensions-dir>

For a new module-style extension or report-style extension, use:

$ civix init-module com.example.mymodule
  (or)
$ civix init-report com.example.myreport

You should tweak the "com.example.mymodule/info.xml" file.

To activate this new module, browse to

  http://mysite.example.com/civicrm/admin/extensions?reset=1&action=browse

where you can refresh the extension list and enable the extension.

== Example: Add a basic web page ==

$ cd com.example.mymodule
$ civix add-page Greeter civirm/greeting
$ vi CRM/Mymodule/Page/Greeter.php
$ vi templates/CRM/Mymodule/Page/Greeter.tpl

Note: At time of writing, you must rebuild the menu to access this page:

  http://mysite.example.com/civicrm/menu/rebuild?reset=1

== Example: Build a .zip file ==

Once you've implemented the extension, you can build a .zip file for
redistribution:

$ cd com.example.mymodule
$ civix build
