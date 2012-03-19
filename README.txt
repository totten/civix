== civix ==

Civix is a command-line tool for building CiviCRM extensions.

== Installation ==

1. If you haven't already, install the PHP tool "composer" (http://getcomposer.org/):

$ cd $HOME
$ curl -s http://getcomposer.org/installer | php

2. Get civix source code and any dependencies:

$ php composer.phar create-project civicrm/civix

4. Add the new civix folder to your shell's PATH, e.g.

$ export PATH=$HOME/civix:$PATH

== Example ==

In this example, we create a new extension with a single web-page:

$ civix init --type=module com.example.mymodule
$ cd com.example.mymodule
$ civix add-page --class=MyPage --path=civirm/mypage
$ vi Mymodule/Page/MyPage.php
$ vi templates/Mymodule/Page/MyPage.tpl
