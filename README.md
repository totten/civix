# civix

Civix is a command-line tool for building CiviCRM extensions. It is distributed as part of [CiviCRM-Buildkit](https://github.com/civicrm/civicrm-buildkit).

## Requirements

* PHP 7.3+
* CiviCRM 5.x (*Recommended: any release from the prior 12 months*)
* (For MAMP, WAMP, XAMPP, etc) PHP command-line configuration (http://wiki.civicrm.org/confluence/display/CRMDOC/Setup+Command-Line+PHP)
* (For CentOS/RHEL) Compatible version of libxml2 (https://github.com/totten/civix/issues/19)

## Download

`civix` is distributed in PHAR format, which is a portable executable file (for PHP). It should run on most Unix-like systems where PHP is installed.
Here are two quick ways to download it:

* Download [the latest release of `civix.phar`](https://download.civicrm.org/civix/civix.phar) (*[SHA256](https://download.civicrm.org/civix/civix.SHA256SUMS),
  [GPG](https://download.civicrm.org/civix/civix.phar.asc)*) and put it in the PATH. For example:

    ```bash
    sudo curl -LsS https://download.civicrm.org/civix/civix.phar -o /usr/local/bin/civix
    sudo chmod +x /usr/local/bin/civix
    ```

    (*Learn more: [Install `civix.phar` as system-wide tool (Linux/BSD/macOS)](#phar-unix)*)

* Use [phar.io's `phive` installer](https://phar.io/) to download, validate, and cache the `civix.phar` file.

    ```bash
    phive install totten/civix
    ```

    (*Learn more: [Install `civix.phar` as project tool (phive)](#phar-phive)*)

There are several more options for downloading `civix`. See also:

* [Download URLs for alternate ersions)](doc/download.md#urls)
* [Comparison of install options](doc/download.md#comparison)
* [Install `civix.phar` as system-wide tool (Linux/BSD/macOS)](doc/download.md#phar-unix)
* [Install `civix.phar` as project tool (composer)](doc/download.md#phar-composer)
* [Install `civix.phar` as project tool (phive)](doc/download.md#phar-phive)
* [Install `civix.git` as system-wide tool (Linux/BSD/macOS)](doc/download.md#src-unix)
* [Install `civix.git` as system-wide tool (Windows)](doc/download.md#src-win)
* [Install `civix.git` as project tool (composer)](doc/download.md#src-composer)

## Documentation

The [CiviCRM Developer Guide](https://docs.civicrm.org/dev/en/latest/) includes [tutorials for building extensions](https://docs.civicrm.org/dev/en/latest/extensions/civix/)

For reference documentation, civix supports a "--help" option.  For example,
to get reference materials about the "generate:page" command, run:

```bash
civix generate:page --help
```

## Development

If you are developing updates for `civix.git`, then see [doc/develop.md](doc/develop.md). It discusses PHAR compilation, unit tests, and similar processes.
