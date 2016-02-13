Civix is a command-line tool for building CiviCRM extensions.

### Requirements

* PHP 5.3+
* CiviCRM 4.2+ (installed from git http://github.com/civicrm)
* [cv](https://github.com/civicrm/cv)
* (For MAMP, WAMP, XAMPP, etc) PHP command-line configuration (http://wiki.civicrm.org/confluence/display/CRMDOC/Setup+Command-Line+PHP)
* (For CentOS/RHEL) Compatible version of libxml2 (https://github.com/totten/civix/issues/19)

### Download

```bash
sudo curl -LsS https://download.civicrm.org/civix/civix.phar -o /usr/local/bin/civix
sudo chmod +x /usr/local/bin/civix
sudo curl -LsS https://download.civicrm.org/cv/cv.phar -o /usr/local/bin/cv
sudo chmod +x /usr/local/bin/cv
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
$ git clone https://github.com/civicrm/civix
$ cd civix
$ composer install
$ php -dphar.readonly=0 `which box` build
```
